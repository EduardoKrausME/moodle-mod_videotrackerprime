// This file is part of Moodle - http://moodle.org/.
/**
 * Learner runtime for pedagogical timeline checkpoints.
 *
 * @module mod_videotrackerprime/runtime
 */
define([
    'core/ajax',
    'core/notification',
    'mod_videotrackerprime/player',
    'local_video_bridge/progress',
    'local_video_bridge/timeline'
], function(Ajax, Notification, Player, Progress, Timeline) {
    const parseConfig = (root) => {
        const node = root.querySelector('[data-region="config"]');
        return node ? JSON.parse(node.textContent || '{}') : {};
    };

    class Runtime {
        constructor(root, config) {
            this.root = root;
            this.config = config;
            this.overlay = root.querySelector('[data-region="checkpoint-overlay"]');
            this.activeCue = null;
            this.sessionid = String(config.sessionid || '');
        }

        initialise() {
            return Player.create(this.root, this.config.player).then((player) => {
                this.player = player;
                const shell = this.root.querySelector('[data-region="player-shell"]') || this.root;
                Progress.attach(player, shell, this.config.player);

                this.timeline = Timeline.create(player, {tolerance: 0.6, maxstep: 4.5});
                const registered = [];
                (this.config.cues || []).forEach((cue) => {
                    if (cue.alreadycompleted && cue.onceonly && !cue.replaynewsession) {
                        return;
                    }
                    this.timeline.register({
                        id: String(cue.id),
                        time: Number(cue.time),
                        type: cue.type,
                        payload: cue,
                        once: Boolean(cue.onceonly)
                    });
                    registered.push({
                        id: String(cue.id),
                        time: Number(cue.time),
                        type: cue.type,
                        title: cue.title,
                        visible: Boolean(cue.visibleontimeline)
                    });
                });
                this.timeline.onTrigger((entry) => this.handleCue(entry.payload || entry));

                const markers = this.root.querySelector('[data-region="timeline-markers"]');
                if (markers) {
                    this.timeline.renderMarkers(markers, registered, {
                        onSelect: (entry) => {
                            if (this.config.capabilities && this.config.capabilities.seeking) {
                                this.timeline.seekTo(entry.time);
                            }
                        }
                    });
                }
                this.bindOverlay();
                if (this.player.onEnded) {
                    this.player.onEnded(() => this.refreshCompletion());
                }
                this.completionTimer = window.setInterval(() => this.refreshCompletion(), 60000);
                return this;
            }).catch((error) => Notification.exception(error));
        }

        bindOverlay() {
            if (!this.overlay) {
                return;
            }
            const submit = this.overlay.querySelector('[data-action="submit"]');
            const dismiss = this.overlay.querySelector('[data-action="dismiss"]');
            submit.addEventListener('click', () => this.submit());
            dismiss.addEventListener('click', () => this.dismiss());
        }

        handleCue(cue) {
            this.activeCue = cue;
            const playbackcontrol = Boolean(this.config.capabilities && this.config.capabilities.playbackcontrol);
            if ((cue.pausevideo || cue.requireinteraction) && playbackcontrol && this.player.pause) {
                this.player.pause();
                this.resumeAfter = true;
            } else {
                this.resumeAfter = false;
            }

            // Passive events are completed at the effective playback crossing.
            if (['message', 'resource', 'alert', 'checkpoint'].includes(cue.type)) {
                this.saveResponse(cue, '').catch(() => {});
            }
            this.renderCue(cue);
        }

        renderCue(cue) {
            if (!this.overlay) {
                return;
            }
            this.overlay.hidden = false;
            this.overlay.querySelector('[data-region="cue-title"]').textContent = cue.title || '';
            this.overlay.querySelector('[data-region="cue-body"]').innerHTML = cue.body || '';
            this.overlay.dataset.type = cue.type;
            this.overlay.querySelectorAll('[data-input-type]').forEach((node) => {
                node.hidden = node.dataset.inputType !== cue.type;
            });

            const reflection = this.overlay.querySelector('[data-input-type="reflection"] textarea');
            if (reflection) {
                reflection.value = '';
                reflection.maxLength = Number(cue.config && cue.config.maxchars || 500);
            }
            const confidence = this.overlay.querySelector('[data-input-type="confidence"]');
            if (confidence) {
                confidence.querySelectorAll('input').forEach((input) => input.checked = false);
            }
            const poll = this.overlay.querySelector('[data-input-type="poll"]');
            if (poll) {
                poll.replaceChildren();
                (cue.config && cue.config.options || []).forEach((option, index) => {
                    const label = document.createElement('label');
                    label.className = 'videotrackerprime-choice';
                    const input = document.createElement('input');
                    input.type = 'radio';
                    input.name = 'videotrackerprime-poll';
                    input.value = option;
                    input.id = 'videotrackerprime-poll-' + cue.id + '-' + index;
                    const text = document.createElement('span');
                    text.textContent = option;
                    label.append(input, text);
                    poll.append(label);
                });
            }
            const resource = this.overlay.querySelector('[data-input-type="resource"] a');
            if (resource) {
                resource.href = cue.config && cue.config.url || '#';
                resource.textContent = cue.config && cue.config.label || cue.title;
            }
            const dismiss = this.overlay.querySelector('[data-action="dismiss"]');
            dismiss.hidden = !cue.dismissible || cue.requireinteraction;
            const submit = this.overlay.querySelector('[data-action="submit"]');
            submit.textContent = ['message', 'resource', 'alert', 'checkpoint'].includes(cue.type)
                ? submit.dataset.continueLabel : submit.dataset.saveLabel;
        }

        responseValue(cue) {
            if (cue.type === 'confirmation') {
                const input = this.overlay.querySelector('[data-input-type="confirmation"] input');
                return input && input.checked ? '1' : '';
            }
            if (cue.type === 'reflection') {
                const input = this.overlay.querySelector('[data-input-type="reflection"] textarea');
                return input ? input.value : '';
            }
            if (cue.type === 'confidence') {
                const input = this.overlay.querySelector('[data-input-type="confidence"] input:checked');
                return input ? input.value : '';
            }
            if (cue.type === 'poll') {
                const input = this.overlay.querySelector('[data-input-type="poll"] input:checked');
                return input ? input.value : '';
            }
            return '';
        }

        submit() {
            const cue = this.activeCue;
            if (!cue) {
                return;
            }
            const response = this.responseValue(cue);
            this.saveResponse(cue, response).then((result) => {
                if (cue.requireinteraction && !result.completed) {
                    return;
                }
                this.closeOverlay();
                this.refreshCompletion();
            }).catch(Notification.exception);
        }

        dismiss() {
            if (!this.activeCue || !this.activeCue.dismissible || this.activeCue.requireinteraction) {
                return;
            }
            this.closeOverlay();
        }

        closeOverlay() {
            this.overlay.hidden = true;
            this.activeCue = null;
            if (this.resumeAfter && this.player.play) {
                this.player.play();
            }
            this.resumeAfter = false;
        }

        saveResponse(cue, response) {
            return Ajax.call([{
                methodname: 'mod_videotrackerprime_save_response',
                args: {
                    checkpointid: Number(cue.id),
                    sessionid: this.sessionid,
                    videotimestamp: Number(this.timeline.getCurrentTime()),
                    response: response || ''
                }
            }])[0];
        }

        refreshCompletion() {
            return Ajax.call([{
                methodname: 'mod_videotrackerprime_refresh_completion',
                args: {cmid: Number(this.config.cmid)}
            }])[0].then((state) => {
                const percent = this.root.querySelector('[data-region="watched-percent"]');
                const completed = this.root.querySelector('[data-region="completed-count"]');
                if (percent) {
                    percent.textContent = String(state.percent) + '%';
                }
                if (completed) {
                    completed.textContent = String(state.completedcheckpoints);
                }
                return state;
            }).catch(() => null);
        }
    }

    const init = () => {
        const root = document.querySelector('[data-videotrackerprime]');
        if (!root) {
            return;
        }
        new Runtime(root, parseConfig(root)).initialise();
    };

    return {init: init};
});
