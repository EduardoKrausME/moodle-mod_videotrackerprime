@mod @mod_videotrackerprime @javascript
Feature: Configure a Video Tracker Prime activity
  In order to add lightweight pedagogical interactions to a video
  As a teacher
  I need an activity that clearly separates video tracking from checkpoint authoring

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher | One |
    And the following "courses" exist:
      | fullname | shortname | numsections |
      | Course 1 | C1 | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |

  Scenario: Teacher sees the checkpoint workflow when adding the activity
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Video Tracker Prime" to section "1" using the activity chooser
    And I expand all fieldsets
    Then I should see "Video source"
    And I should see "Save the activity first, then use “Manage checkpoints” to place pedagogical events on the video timeline."
