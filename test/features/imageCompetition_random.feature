Feature:
  In order to test the image competition I need to use behat.

  Scenario: Get image entries
    As a programmer
    Given I create a competition
    And I upload 4 image entries
    When I get the images for the created competition
    Then the response code should be 200
    And the response should match schema "imageEntriesSchema.json"
    And the last response should contain the created images
    When I test the random order, it should behave correctly


