# WakeOnWeb Behat Contexts

Provide some simple behat contexts

```
default:
    suites:
        default:
            contexts:
                - WakeOnWeb\BehatContexts\AmqpContext:
                    transports: 
                        # I intentionally did not use syntax %env()% because BEHAT doesn't fully
                        # support this case since Behat and Symfony kernel are not the sames.
                        async_internal: "env(MESSENGER_TRANSPORT_ASYNC_INTERNAL_DSN)"
                        my_second_queue: "DIRECT_DSN"
                    # you can define your own Adapter, it musts implements \WakeOnWeb\BehatContexts\AmqpAdapter\AdapterInterface;
                    # adapterClass: \WakeOnWeb\BehatContexts\AmqpAdapter\SymfonyMessengerAdapter
                    # Create queues if they don't exist.
                    # setupQueuesAutomatically: 1
                - WakeOnWeb\BehatContexts\FidryAliceFixturesContext:
                    # optional
                    # default is %kernel.project_dir%/tests/fixtures
                    # basepath: /var/www/.... 
                - WakeOnWeb\BehatContexts\DoctrineORMSchemaReloadContext
                - WakeOnWeb\BehatContexts\HttpMockContext:
                    # basepath is %kernel.project_dir%/
                    # optional
                    # mockServers:                    
                        - {serverName: 'user', port: 8870, host: 'localhost', basePath: '/admin'}
                        - {serverName: 'mailer'}
                    # bodyRequestPath: tests/functional/fixtures/request (default)
                - WakeOnWeb\BehatContexts\SwaggerContext:
                    # basepath is %kernel.project_dir%/
                    # swaggerFile: doc/mySwaggerFile.yaml
                - WakeOnWeb\BehatContexts\FileContext:
                    # optional
                    # basePath: 'test/funtional/fixtures/file'
            paths:
                - tests/Features
```

## AmqpContext

If feature/scenario has tag @amqp, it'll automatically remove messages in all queues defined on context.


## FidryAliceFixturesContext

Needs [AliceDataFixtures](https://github.com/theofidry/AliceDataFixtures) and its bundle to be installed.

## DoctrineORMSchemaReloadContext

Needs [DoctrineBundle] to be installed.

If feature/scenario has tag @database, it'll automatically delete/create doctrine schema for all managers.

You have to create database before by yourself.

## HttpMockContext

Needs 
    [BehatSymfony2Extension](https://github.com/Behat/Symfony2Extension),
    [InternationHttpMock](https://github.com/InterNations/http-mock)
    
Configuration:

 - mockServers: A mock server set up list. Only name is mandatory.
 - bodyRequestPath: Base path for target json response file.

Usage:

You have two way for set up a list of mock. 
1. Configuration with tag

    Add your mocks in the suit configuration and use the @mockable tag on the feature or scenario.
2. Steps

    Use "I set up mock servers:" step and don't forget "I tear down mock servers" after use it.
    
Steps available:
 
    Given I set up mock servers:
        | name | port | host | basePath |
    Then I tear down mock servers
    Given A :method request on :path to the mock server :serverName must be return a :responseCode response
    Given A :method request on :path to the mock server :serverName must be return a :responseCode response with content :body
    Given A :method request on :path to the mock server :serverName must be return a :responseCode response with file :bodyFileName as content   
    Given A :method request on path matching :regex to the mock server :serverName must be return a :responseCode response
    Given A :method request on path matching :regex to the mock server :serverName must be return a :responseCode response with content :body
    Given A :method request on path matching :regex to the mock server :serverName must be return a :responseCode response with file :bodyFileName as content

## SwaggerContext

Needs:
    [BehatSymfony2Extension](https://github.com/Behat/Symfony2Extension),
    [UbirakRestApiBehatExtension](https://github.com/ubirak/rest-api-behat-extension),
    [SwaggerValidator](https://github.com/WakeOnWeb/swagger),

How to use:

- Set the path of your yaml swagger file (V2 only) in the behat.yml file. 
- Use the step after call the endpoint with UbirakRestApi

Steps available:

    Then I validate Swagger response on :path with :method method and statusCode :statusCode
    
## FileContext

Needs:
    [BehatSymfony2Extension](https://github.com/Behat/Symfony2Extension),

How to use:

- Set the base path of your fixture file folder (optional, default: tests/functional/fixtures/file)
- Use steps
- Use the @clean-files-after or @clean-files-before tag for clean file created with step "I create file in :path from :file"
- If the scenario generate files, you can clean it with step "I clean all files from folder :folder"

Steps available:

    Given I create file in :path from :file
    Then The file :file must be a copy of :fileExpected
    Then One of file present in :path must be a copy of :fileExpected
    Then The file :file size is less or equal to :size octet
    Then The file :file size is equal to :size octet
    Then The file :file mime type must be equal to :mimeType
    Then The file :file mime type must be equal to one of following:
    Then :number files must be present in folder :path
    Then The file with name :fileName must be present in :path
    Then I clean all files from folder :folder
    Then I clean generated files
