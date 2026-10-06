# Video Tracker Prime

Video Tracker Prime is a Moodle activity for adding small pedagogical events to a video timeline while keeping playback, providers, progress and normalized tracking in `local_video_bridge`.

Its purpose is deliberately narrower than a video quiz. Prime does not use Moodle Question Bank, does not implement question attempts, grading engines or complex assessment logic, and is not intended to replace a dedicated `mod_videoquiz`. The activity adds microinteractions synchronized with effective playback.

## What belongs to Prime

Teachers can place these event types at precise timestamps:

- message;
- confirmation;
- short reflection;
- confidence scale from 1 to 5;
- ungraded poll;
- complementary link/resource;
- alert;
- passage checkpoint.

Each event can be required, pause the video, require interaction before playback continues, allow dismissal, trigger once per session, trigger again in a later session, appear as a marker on the timeline, and use optional start/end availability dates.

The visual editor plays the same configured video and lets the teacher use **Add event at this point** so the current player position becomes the initial timestamp. Existing markers can be selected, duplicated, deleted and dragged to propose a new timestamp when the provider exposes reliable duration/seeking.

## What belongs to Video Bridge

Prime depends on `local_video_bridge` and does not implement provider-specific playback code. The bridge supplies:

- source/provider discovery;
- the normalized player adapter;
- reliable tracking capability checks;
- watched progress and viewing map;
- current position and duration;
- play/pause/seek APIs when the provider guarantees them;
- the generic timeline cue engine.

Only providers declaring reliable `tracking` are offered by Prime. A checkpoint that pauses playback or blocks continuation additionally requires the provider capability `playbackcontrol`; this is validated on the server and reflected in the editor.

The bridge timeline engine is intentionally semantic-free: it knows that a cue exists at a time and that effective continuous playback crossed it, but it does not know what a reflection, confidence scale or poll means. Those concepts remain in this activity.

Seeking across a checkpoint does not complete it. The cue engine treats jumps as discontinuities and only triggers the checkpoint after effective playback reaches/crosses its timestamp.

## Data model

Besides the normal Moodle activity table, Prime stores only two domain datasets:

- `videotrackerprime_cues`: the checkpoint definitions;
- `videotrackerprime_answers`: the learner passage/interaction records.

Playback progress is not copied into Prime. Reports and completion read normalized progress from `local_video_bridge`.

Each interaction stores the learner, checkpoint, client playback session, actual video timestamp, response when applicable, completion flag and timestamps. The learner API never accepts a target user id, so a user cannot use it to edit another learner's response.

## Completion

Automatic completion rules can be combined:

- minimum watched percentage from Video Bridge;
- all checkpoints marked as required;
- at least X checkpoints completed.

When multiple rules are enabled, all enabled conditions must be satisfied.

## Dashboard

The report combines bridge progress with Prime interactions and shows:

- watched percentage by learner;
- completed and pending checkpoint counts;
- how many learners effectively passed each point;
- reflection responses;
- average confidence score;
- poll response distribution.

Moodle groups are respected when the activity uses separate groups.

## Security and Moodle APIs

The plugin uses module context validation, capabilities, Moodle External API validation, `require_login`, server-side source capability checks, Moodle text cleaning/formatting, Privacy API, Backup/Restore and custom completion. AJAX requests use Moodle's authenticated External API and session key handling rather than custom unauthenticated endpoints.

## Architecture boundary

A useful rule is: if the feature is about *how the video is played or where playback really is*, it belongs in `local_video_bridge`; if it is about *what the learner should see or do at that point*, it belongs in Video Tracker Prime.

That boundary is why Prime can share the same playback infrastructure with other video activities without turning the bridge into a pedagogical monolith.
