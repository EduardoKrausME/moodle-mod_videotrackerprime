// This file is part of Moodle - http://moodle.org/.
/**
 * Visual timeline editor.
 *
 * @module mod_videotrackerprime/editor
 */
define([
    'core/ajax',
    'core/notification',
    'mod_videotrackerprime/player',
    'local_video_bridge/timeline'
], function(Ajax, Notification, Player, Timeline) {
    const parseConfig = (root) => JSON.parse(root.querySelector('[data-region="config"]').textContent || '{}');

    class Editor {
        constructor(root, config) {
            this.root = root;
            this.config = config;
            this.form = root.querySelector('[data-region="checkpoint-form"]');
            this.list = root.querySelector('[data-region="checkpoint-list"]');
        }

        initialise() {
            return Player.create(this.root, this.config.player).then((player) => {
                this.player = player;
                this.timeline = Timeline.create(player, {tolerance: 0.6, maxstep: 4.5});
                this.cues = Array.isArray(this.config.cues) ? this.config.cues.slice() : [];
                this.bind();
                this.render();
            }).catch(Notification.exception);
        }

        bind() {
            this.root.querySelector('[data-action="add-current"]').addEventListener('click', () => {
                this.resetForm();
                this.form.querySelector('[name="timestamp"]').value =
                    Number(this.player.getCurrentTime() || 0).toFixed(2);
                this.form.scrollIntoView({behavior: 'smooth', block: 'nearest'});
            });
            this.form.addEventListener('submit', (event) => {
                event.preventDefault();
                this.save();
            });
            this.form.querySelector('[data-action="cancel"]').addEventListener('click', () => this.resetForm());
            this.form.querySelector('[name="type"]').addEventListener('change', () => this.updateTypeFields());
            this.list.addEventListener('click', (event) => {
                const action = event.target.closest('[data-action]');
                if (!action) {
                    return;
                }
                const id = Number(action.dataset.id || 0);
                const cue = this.cues.find((item) => Number(item.id) === id);
                if (!cue) {
                    return;
                }
                if (action.dataset.action === 'edit') {
                    this.load(cue);
                } else if (action.dataset.action === 'duplicate') {
                    this.load(Object.assign({}, cue, {id: 0, title: cue.title + ' (copy)'}));
                } else if (action.dataset.action === 'delete') {
                    Notification.confirm('', action.dataset.confirm, action.dataset.yes, action.dataset.no,
                        () => this.remove(id));
                } else if (action.dataset.action === 'seek') {
                    this.timeline.seekTo(Number(cue.time));
                }
            });
        }

        resetForm() {
            this.form.reset();
            this.form.querySelector('[name="id"]').value = '0';
            this.form.querySelector('[name="timestamp"]').value = Number(this.player.getCurrentTime() || 0).toFixed(2);
            this.updateTypeFields();
        }

        load(cue) {
            this.resetForm();
            const set = (name, value) => {
                const input = this.form.querySelector('[name="' + name + '"]');
                if (!input) {
                    return;
                }
                if (input.type === 'checkbox') {
                    input.checked = Boolean(value);
                } else {
                    input.value = value == null ? '' : String(value);
                }
            };
            set('id', cue.id);
            set('timestamp', cue.time);
            set('title', cue.title);
            set('body', cue.rawbody || '');
            set('type', cue.type);
            set('required', cue.required);
            set('pausevideo', cue.pausevideo);
            set('requireinteraction', cue.requireinteraction);
            set('dismissible', cue.dismissible);
            set('onceonly', cue.onceonly);
            set('replaynewsession', cue.replaynewsession);
            set('visibleontimeline', cue.visibleontimeline);
            set('timestart', cue.timestart || 0);
            set('timeend', cue.timeend || 0);
            set('sortorder', cue.sortorder || 0);
            if (cue.type === 'poll') {
                set('options', (cue.config && cue.config.options || []).join('\n'));
            } else if (cue.type === 'resource') {
                set('resourceurl', cue.config && cue.config.url || '');
                set('resourcelabel', cue.config && cue.config.label || '');
            } else if (cue.type === 'reflection') {
                set('maxchars', cue.config && cue.config.maxchars || 500);
            }
            this.updateTypeFields();
            this.form.scrollIntoView({behavior: 'smooth', block: 'nearest'});
        }

        updateTypeFields() {
            const type = this.form.querySelector('[name="type"]').value;
            this.form.querySelectorAll('[data-for-type]').forEach((node) => {
                node.hidden = node.dataset.forType !== type;
            });
            const hasControl = Boolean(this.config.capabilities && this.config.capabilities.playbackcontrol);
            ['pausevideo', 'requireinteraction'].forEach((name) => {
                const input = this.form.querySelector('[name="' + name + '"]');
                input.disabled = !hasControl;
                if (!hasControl) {
                    input.checked = false;
                }
            });
            const warning = this.form.querySelector('[data-region="playback-warning"]');
            warning.hidden = hasControl;
        }

        values() {
            const data = new FormData(this.form);
            const value = (name) => data.get(name) || '';
            const checked = (name) => this.form.querySelector('[name="' + name + '"]').checked;
            return {
                cmid: Number(this.config.cmid),
                id: Number(value('id') || 0),
                timestamp: Number(value('timestamp') || 0),
                title: value('title'),
                body: value('body'),
                type: value('type'),
                options: value('options'),
                resourceurl: value('resourceurl'),
                resourcelabel: value('resourcelabel'),
                maxchars: Number(value('maxchars') || 500),
                required: checked('required'),
                pausevideo: checked('pausevideo'),
                requireinteraction: checked('requireinteraction'),
                dismissible: checked('dismissible'),
                onceonly: checked('onceonly'),
                replaynewsession: checked('replaynewsession'),
                visibleontimeline: checked('visibleontimeline'),
                timestart: Number(value('timestart') || 0),
                timeend: Number(value('timeend') || 0),
                sortorder: Number(value('sortorder') || 0)
            };
        }

        save() {
            Ajax.call([{
                methodname: 'mod_videotrackerprime_save_checkpoint',
                args: this.values()
            }])[0].then(() => window.location.reload()).catch(Notification.exception);
        }

        remove(id) {
            Ajax.call([{
                methodname: 'mod_videotrackerprime_delete_checkpoint',
                args: {cmid: Number(this.config.cmid), checkpointid: Number(id)}
            }])[0].then(() => window.location.reload()).catch(Notification.exception);
        }

        render() {
            this.list.replaceChildren();
            this.cues.sort((a, b) => Number(a.time) - Number(b.time));
            this.cues.forEach((cue) => {
                const row = document.createElement('div');
                row.className = 'videotrackerprime-editor-row';
                row.innerHTML =
                    '<button type="button" class="btn btn-link p-0" data-action="seek" data-id="' + Number(cue.id) + '">' +
                    this.formatTime(cue.time) + '</button>' +
                    '<strong></strong><span class="badge bg-secondary"></span>' +
                    '<div class="videotrackerprime-editor-actions">' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary" data-action="edit"></button>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary" data-action="duplicate"></button>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger" data-action="delete"></button></div>';
                row.querySelector('strong').textContent = cue.title;
                row.querySelector('.badge').textContent = cue.type;
                const buttons = row.querySelectorAll('[data-action]');
                buttons.forEach((button) => button.dataset.id = String(cue.id));
                const edit = row.querySelector('[data-action="edit"]');
                edit.textContent = edit.dataset.label = this.root.dataset.editLabel;
                const dup = row.querySelector('[data-action="duplicate"]');
                dup.textContent = this.root.dataset.duplicateLabel;
                const del = row.querySelector('[data-action="delete"]');
                del.textContent = this.root.dataset.deleteLabel;
                del.dataset.confirm = this.root.dataset.deleteConfirm;
                del.dataset.yes = this.root.dataset.yesLabel;
                del.dataset.no = this.root.dataset.noLabel;
                this.list.append(row);
            });

            const markerRoot = this.root.querySelector('[data-region="timeline-markers"]');
            this.timeline.renderMarkers(markerRoot, this.cues.map((cue) => ({
                id: String(cue.id),
                time: Number(cue.time),
                type: cue.type,
                title: cue.title,
                visible: Boolean(cue.visibleontimeline)
            })), {
                draggable: true,
                onSelect: (entry) => this.timeline.seekTo(entry.time),
                onMove: (entry, newTime) => {
                    const cue = this.cues.find((item) => String(item.id) === String(entry.id));
                    if (!cue) {
                        return;
                    }
                    cue.time = newTime;
                    this.load(cue);
                }
            });
            this.resetForm();
        }

        formatTime(seconds) {
            seconds = Math.max(0, Math.floor(Number(seconds) || 0));
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }
    }

    const init = () => {
        const root = document.querySelector('[data-videotrackerprime-editor]');
        if (root) {
            new Editor(root, parseConfig(root)).initialise();
        }
    };
    return {init: init};
});
