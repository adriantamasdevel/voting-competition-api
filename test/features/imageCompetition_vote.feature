Feature:
  In order to test the image competition I need to use behat.

  Scenario: Get image entries
    As a programmer
    Given I create a competition
    Then the competition stats should be:
      | imageEntryCount | 0 |
      | imageEntryUnmoderatedCount | 0  |
      | imageEntryVerifiedCount | 0  |
      | imageEntryHiddenCount | 0  |
      | imageEntryBlockedCount | 0  |
      | votesCount | 0 |
    And I upload 4 image entries
    Then the competition stats should be:
      | imageEntryCount | 4 |
      | imageEntryUnmoderatedCount | 0  |
      | imageEntryVerifiedCount | 4  |
      | imageEntryHiddenCount | 0  |
      | imageEntryBlockedCount | 0  |
      | votesCount | 0 |
    When 3 votes are cast for image 0
    When 2 votes are cast for image 1
    When 1 votes are cast for image 2
    Then the image scores should be:
      | image | expectedScore |
      | 0   | 3   |
      | 1   | 2   |
      | 2   | 1   |
      | 3   | 0   |
    Then the competition stats should be:
      | imageEntryCount | 4 |
      | imageEntryUnmoderatedCount | 0  |
      | imageEntryVerifiedCount | 4  |
      | imageEntryHiddenCount | 0  |
      | imageEntryBlockedCount | 0  |
      | votesCount | 6 |

