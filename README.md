# image-voting-competition-api




In the directory `/data/image-voting-competition-api` run `composer update`

To ensure the database is created:

```
vagrant ssh imagecomp_api_app
cd /data/image-voting-competition-api
php src/cli.php db:migrate
```

The Swagger UI should then be available at:
http://localhost/swagger/index.html
and
http://localhost/swagger/index.html

The admin version makes authenticated calls, the one without admin makes un-authenticated (aka user) calls.

Please use the Swagger UI to create a competition.


The upload image page should then be available at:
http://localhost/upload.html

The competition display page is at:
http://localhost/competition-display.html

The moderation page is at:
http://localhost/admin/



## API details


### Competition status

STATUS_ANNOUNCED - before people can submit a photo
STATUS_OPEN - people can submit images and vote on them
STATUS_VOTING - people can't submit images but they can still vote
STATUS_CLOSED - people can't submit images or vote
STATUS_HIDDEN - public can't see the competition

To be clear, images can only be submitted when the competition status is STATUS_OPEN and voting can only be done when the competition status is STATUS_VOTING. The status of competitions can be changed through the swagger interface.


### Rules for entering images

The rules that are followed as to whether a user can enter images are:

1. The competition must be in the state STATUS_OPEN
2. The `dateEntriesClose` must be set to a datetime that is in the future.

 Currently the status of the competition is updated by hand, so it is possible for there to be a mismatch between the two rules, i.e. the `dateEntriesClose` could pass, and the admin forgets to change the status to STATUS_VOTING. The result of this is that the competition would appear to be open for entries, however the API would not accept any new entries.

### Rules for voting

 The rules that are followed as to whether a user can vote on imageEntries are:

1. The competition must be in either the state STATUS_OPEN or STATUS_VOTING
2. The `dateVotesClose` must be set to a datetime that is in the future.

Currently the status of the competition is updated by hand, so it is possible for there to be a mismatch between the two rules, i.e. the `dateVotesClose` could pass, and the admin forgets to change the status to STATUS_CLOSED. The result of this is that the competition would appear to be open for voting, however the API would not accept any new votes.


### Results sorting

Most of the results can be sorted in a normal way e.g. the imageEntries can be sorted by dateSubmitted by setting the sort parameter to `dateSubmitted`, which returns the results in a normal sorted way.

However both the /imageEntries and /imageEntriesWithScore end-points can also be sorted randomly, by setting the sort parameter to `rand`.

When the sorting is set to random, to allow the full set of data to be paged through, an additional random-token seed is returned with the data.

```
{
  "data": {
    "imageEntries": [
      {
        "imageId": "1234",
        "description": "image description",
        "dateSubmitted": "2016-07-21T16:35:23+0000",
        "competitionId": 69,
        "imageURL": "..."
      },
      {
        "imageId": "12345",
        "description": "image description",
        "dateSubmitted": "2016-07-21T16:35:26+0000",
        "competitionId": 69,
        "imageURL": "..."
      }
    ],
    "randomToken": "{\"time\":1469438100,\"numberEntries\":4,\"seed\":55}"
  },
  "pagination": {
    "offset": 0,
    "returned": 2,
    "limit": 20,
    "total": 4
  }
}
```

This random-token seed can be set as the randomToken variable on subsequent requests with an offset value set to use the same random ordering of the images and page through the data. Example:

http://localhost/v1/imageEntries?competitionIdFilter=69&randomToken=%7B%5C%22time%5C%22%3A1469438100%2C%5C%22numberEntries%5C%22%3A4%2C%5C%22seed%5C%22%3A55%7D&offset=2&sort=rand

Although the randomToken looks like a JSON object, it should just be treated as a string; the information embedded in the randomToken is exposed to make debugging easier, but may change at any time.

Additionally - it is possible for the backend of the API to return a new random token after each API call. Only the latest randomToken should be used. i.e. each randomToken returned is only used to make the next request, it shouldn't be stored permanently.

The exact conditions under which the randomToken will be renewed are still to be determined, but will include when the old random token is too out of date, either by time, or by there being significantly more images entered in the competition than when the randomToken was generated.

Additionally, to allow debugging to be easier, there is a 'hidden' API parameter of `renewRandomToken` which will force the randomToken to be renewed by the api.


### ImageEntry status

STATUS_UNMODERATED - the initial status after submission.
STATUS_VERIFIED - after the image entry has been moderated.
STATUS_HIDDEN - when a moderator has chosen to hide the image from general view.
STATUS_BLOCKED - when a moderator has chosen to hide the image from the moderator view as well. Bringing images back from this state would be tricky currently.


### Error codes

We now have multiple places in the code where the API will be generating a 403 'forbidden' response, but the specific cause of the 403 error will have different possible causes.

The front-end calls to the API will need to check the 'ErrorCode' that should be set in the error response, and pick the specific meaning from these values:

* ERROR_VOTING_IS_NOT_OPEN_FOR_COMPETITION - 120
* ERROR_VOTE_FROM_IP_ADDRESS_EXISTS - 121
* ERROR_IMAGE_ENTRY_NOT_OPEN_FOR_COMPETITION - 122
* ERROR_FORM_ERRORS - 123

Most of the error codes just indicate an single error state - and the frontend should take the appropriate action.

For the error ERROR_FORM_ERRORS (aka 123) the API returns user-facing messages, which can be displayed to the user. The errors for individual fields are included in the JSON object returned by the API, and are in JSON pointer notation.

{
  "StatusCode": 403,
  "DeveloperMessage": "Form has errors",
  "UserMessage": "Form has errors",
  "ErrorCode": 123,
  "formErrors": [
    {
      "name": "/email",
      "reason": "Email too long, maximum length is 256"
    },
    {
      "name": "/firstName",
      "reason": "First name too long, maximum length is 70"
    },
    {
      "name": "/description",
      "reason": "Description too long, maximum length is 2048"
    },
    {
      "name": "/lastName",
      "reason": "Last name too long, maximum length is 70"
    }
  ]
}



## Running unit tests

In the root directory of the project run:

```
php vendor/bin/phpunit -c test/phpunit.xml
```




Running tests
=============

vendor/bin/behat test/features/imageCompetition_random.feature

Or

vendor/bin/behat test/features/imageCompetition_vote.feature




Running Selenium
================

Selenium requires a later version of Java than is available by default on OSX. Downloading the latest version of Java from Oracle will get you a version that Selenium works on.

However you will need to tell your computer to use this version, the easiest way to do that is to alias it:

alias java="/Library/Internet\ Plug-Ins/JavaAppletPlugin.plugin/Contents/Home/bin/java"

so that the downloaded version is used.


Running against dev, staging or live
====================================

The tests read the environment variable TEST_ENV to determine which environment to test against. To select a specific environment, run one of these lines before running the tests.


export TEST_ENV=dev
export TEST_ENV=stage
export TEST_ENV=live


php -d memory_limit=2048M /docs/apps/composer.phar update