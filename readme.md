Swagger Docs Generator
================================

This is a documentation generator for [swagger-ui](https://github.com/wordnik/swagger-ui). It will scan your project source code, extract the comment with special beginning, parse the swagger code within the comment
and generate the swagger documentation.

## 3 Steps to use the swagger docs generator

- `Documentation`: write the swagger doc for your project
- `Configuration`: specify the `project root path`, `your api url` and `swagger doc url` in the config.json
- `Run & Export`: run the doc generator and copy all the generated docs to location that can be directly accessed by the `swagger doc url` defined in step 2 and modify the swagger-ui's index.html file

### 1 Documentation
All the swagger doc should be place within the comment started with `/**`

#### Resource

One resource could contain any number of APIs

```php
/**
 * @resource /path
 */
```
__@resource:__ the relative path of the API

Generated Swagger doc would be:

```json
{
    "resource": ${/path},
    ...
}
```

#### API

```php
/**
  * @api apiName
  *
  * @desc (short description of the api)
  *
  * @url /relative/path/to/api/root/url
  *
  * @method httpMethod
  *
  * @param type name [restriction.] [description of the param]
  *
  * @status statusCode (reason why the occuried)
  *
  * @return returnType
  */
```

__@api:__ Name for the API
- syntax: `@api apiName`
- noted: the apiName is a one-word name

__@desc:__ Description for the API

- syntax: `@desc short description of this API`

__@url:__ The relative url based on the API root url which will be stated in Step 2

- syntax: `@url /relative/path/to/api/root/url`

- Optional path parameter
    - Since the swagger doesn't support optional path parameter, If you need it. Use the [swagger-ui](https://github.com/ytsTony/swagger-ui) I forked.
    - syntax: `/url/contain(/{optional})`

__@method:__ the http method of the request

- syntax: `@method httpMethod`
- noted: the valid HTTP method included `GET`, `POST`, `PUT` and `HEAD`

__@param:__ the parameter sent with the request

- syntax `@param type name [restriction.] [description of the param]`
    - type: the data type of the parameter.
    - name: the name of the parameter. can be ommited if its type is not primitive
    - restriction (_optional_): the restriction of the parameter.
        - syntax: `restriction1, restriction2 .`
        - noted: the restriction must split by comma (,) and stoped by fullstop (.)
        - valid restriction are __required__ and __multiple__

The generated Swagger doc would be

```json
@param string foo required. foo description
{
    "dataType": "string",
    "name": "foo",
    "required": true,
    "allowMultiple": false,
    "paramType": ${auto generated},
    "description": "foo description"
}
```

__@status:__ the response status of the API

- syntax `@status statusCode reason why the occuried`
    - statusCode: the status code of the response such as 200, 404
    - reason: a text description of the reason why such code occuried

__@return__ the response type

- noted: the response type could be array
- syntax: `LIST[type]`

Generated Swagger doc would be:

```json
{
    "path": ${/relative/path/to/api/root/url},
     "operations": [
         {
             "nickname": ${apiName},
             "httpMethod": ${httpMethod},
             "notes": ${short description of the api},
             "responseClass": ${returnType},
             "parameters": [
                {
                    "dataType": ${type},
                    "name": ${name},
                    "required": ${is required restriction stated},
                    "allowMultiple": ${is multiple restriction stated},
                    "paramType": ${auto generated},
                    "description": ${description}
                }
             ],
             "errorResponses": [
                 {
                     "code": ${statusCode},
                     "reason": ${reason why the occuried}
                 }
             ]
         }
     ]
}
```


#### Model

One model might contain any number of properties

```php
/**
 * @model modelName
 */
```
__@model:__
- syntax `@model modelName`
- noted: the modelName is a one-word name

Generated Swagger doc would be:

```json
${modelName}: {
    "id": ${modelName}
    ...
}
```

#### Property

```php
/**
 * @property name:type description
 */
```

__@property:__ the property of the model

- syntax: `@property name:type description`
    - name: the name of the property
    - type: the type of the property could be one of the following three
        - normal: a primitive or complex type
        - range type:
            - syntax: `min-max`
            - noted: min is the lower bound of the range while max is the upper bound. no space within the expression
        - list type (or enum):
            - syntax: `primitiveType(enum1|enum2|emun3)`
            - noted all the enums must be splited by |.
    - description of the property

Generated Swagger doc would be:

```json
# 1 normal type
# @property name:type description
#
"properties": {
    ${name}: {
        "type": ${type},
        "description": ${description}
    }
    ...
}

# 2. enums
# @property name:type(enum1|enum2|enum3) description
#
"properties": {
    ${name}: {
        "type": ${type}
        "description": ${description},
        "allowableValues":{
          "valueType":"LIST",
          "values":[
            ${enum1},
            ${enum2},
            ${enum3}
          ]
        }
    }
    ...
}

# 3. range
# @property name:1-10 description
#
"properties": {
    ${name}: {
        "type": ${type}
        "description": ${description},
        "allowableValues": {
          "valueType": "RANGE",
          "min": ${min},
          "max": ${max}
        }
    }
    ...
}
```

### 2 Configuration
#### config.js

```json
{
    "discoverPath" : [
        "project/dir/path/to/scan"
    ],
    "outputDir" : "dir/path/to/output",
    "excludedPath" : [
        "dir/path/not/to/scan"
    ],
    "apiVersion": "[api version]",
    "swaggerVersion": "1.0",
    "basePath" : "[your api root url]",
    "resourceListPath" : "[your swagger doc url]",
    "templatePath": "template.json"
}
```


The configuration is placed in the config.json file.
- __discoverPath:__  state the project directories want to be scaned. \
- __outputDir:__ all the generated swagger docs will be placed to this directory.
- __excludedPath:__  the path you don't want the program to scan.
- __apiVersion:__  the api version
- __swaggerVersion:__  the swagger version. we use 1.0 now
- __basePath:__  the api root url
- __resourceListPath:__  the swagger doc url which can be accessed by internet
- __templatePath:__  the template configuration for the interpreter.

All the fields are required.

### 3 Run & Export

Run the swagger-php-cli.php program

```
$ php swagger-php-cli.php
```

All the swagger docs will be placed to the outputDir directory stated in the config file. Move to the resouceListPath your stated in the config file.

Modify the index.html file of the swagger-ui. Make its discover path point to the url of your location of api-docs.json

## Samples
the sample folder contain a set of comprehensive samples.

```php
############# SampleAPI.php
<?php
/**
 * @resource /sampleAPI
 */
class SampleAPI {
    /**
     * @api api1
     *
     * @url /sampleAPI/api1
     *
     * @desc this is a foo API
     *
     * @method POST
     *
     * @param SampleModel required. this is a bar
     *
     * @return SampleModel(sample:SampleModel)
     */
    public function api1() { }
    /**
     * @api api2
     *
     * @url /sampleAPI/api2
     *
     * @desc this is a foo API
     *
     * @method GET
     *
     * @param int foo required, multiple. this is a foo
     *
     * @return LIST[SampleModel]
     */
    public function api2() { }
}

############# SampleModel.php
<?php

/**
 * @model SampleModel
 */
class SampleModel {
    /**
     * @property id:string the id
     */
    public $id;

    /**
     * @property range:1-100 the valid range is from 1-100
     */
    public $range;

    /**
     * @property enum:string(enum1|enum2|enum3)
     */
    public $enum;

    /**
     * @property sample:string
     */
    public $sample;
}
```


```json
############# Generated Swagger doc
{
    "resource": "\/sampleAPI",
    "basePath": "http:\/\/foo.com",
    "apiVersion": "1.0",
    "swaggerVersion": "1.0",
    "apis": [
        {
            "path": "\/sampleAPI\/api1",
            "operations": [
                {
                    "nickname": "api1",
                    "notes": "this is a foo API",
                    "httpMethod": "POST",
                    "responseClass": "SampleModel(sample:SampleModel)",
                    "parameters": [
                        {
                            "dataType": "SampleModel",
                            "name": "SampleModel",
                            "required": true,
                            "allowMultiple": false,
                            "paramType": "body",
                            "description": " this is a bar"
                        }
                    ]
                }
            ]
        },
        {
            "path": "\/sampleAPI\/api2",
            "operations": [
                {
                    "nickname": "api2",
                    "notes": "this is a foo API",
                    "httpMethod": "GET",
                    "responseClass": "LIST[SampleModel]",
                    "parameters": [
                        {
                            "dataType": "int",
                            "name": "foo",
                            "required": true,
                            "allowMultiple": true,
                            "paramType": "query",
                            "description": " this is a foo"
                        }
                    ]
                }
            ]
        }
    ],
    "models": {
        "SampleModel(sample:SampleModel)": {
            "id": "SampleModel(sample:SampleModel)",
            "properties": {
                "id": {
                    "type": "string"
                },
                "range": {
                    "type": "int",
                    "allowValues": {
                        "valueType": "RANGE",
                        "max": 100,
                        "min": 1
                    }
                },
                "enum": {
                    "type": "string",
                    "allowValues": {
                        "valueType": "LIST",
                        "values": [
                            "num1",
                            "enum2",
                            "enum3"
                        ]
                    }
                },
                "sample": {
                    "type": "SampleModel"
                }
            }
        },
        "SampleModel": {
            "id": "SampleModel",
            "properties": {
                "id": {
                    "type": "string"
                },
                "range": {
                    "type": "int",
                    "allowValues": {
                        "valueType": "RANGE",
                        "max": 100,
                        "min": 1
                    }
                },
                "enum": {
                    "type": "string",
                    "allowValues": {
                        "valueType": "LIST",
                        "values": [
                            "num1",
                            "enum2",
                            "enum3"
                        ]
                    }
                },
                "sample": {
                    "type": "string"
                }
            }
        }
    }
}
```

the inline_sample folder give a example for the inline style swagger annotation

```php
<?php
class InlineStyleSample {
    /**
     * @resource /inlineStyle
     *
     * @api foofoo
     *
     * @url /foo/foofoo
     *
     * @desc this is a foo API
     *
     * @method POST
     *
     * @param Bar required. this is a bar
     *
     * @return BarBar(bar:Bar)
     *
     * @model Bar
     *
     * @property barbar:BarBar barbarbar
     *
     * @model BarBar
     *
     * @property bar:string barbabrabrbarb ab
     *
     */
    public function foofoo($Bar) {

    }
}
```


```json
## Generated Swagger Docs
{
    "resource": "\/inlineStyle",
    "basePath": "http:\/\/foo.com",
    "apiVersion": "1.0",
    "swaggerVersion": "1.0",
    "apis": [
        {
            "path": "\/foo\/foofoo",
            "operations": [
                {
                    "nickname": "foofoo",
                    "notes": "this is a foo API",
                    "httpMethod": "POST",
                    "responseClass": "BarBar(bar:Bar)",
                    "parameters": [
                        {
                            "dataType": "Bar",
                            "name": "Bar",
                            "required": true,
                            "allowMultiple": false,
                            "paramType": "body",
                            "description": " this is a bar"
                        }
                    ]
                }
            ]
        }
    ],
    "models": {
        "BarBar(bar:Bar)": {
            "id": "BarBar(bar:Bar)",
            "properties": {
                "bar": {
                    "type": "Bar"
                }
            }
        },
        "Bar": {
            "id": "Bar",
            "properties": {
                "barbar": {
                    "type": "BarBar"
                }
            }
        },
        "BarBar": {
            "id": "BarBar",
            "properties": {
                "bar": {
                    "type": "string"
                }
            }
        }
    }
}
```

## Feature in Beta
Support model creation

For example:

```php
# Create two model Foo and Bar
/**
 * @model Foo
 *
 * @property id:string
 * @property bar:string
 *
 * @model Bar
 *
 * @property bid:int
 */

# Within some APIs
/**
 * ...
 * @return Foo(bar:Bar)
 */

 In this case a new mode Foo(bar:Bar) is created. Its propotype as follow:
 /**
  * @model Foo(bar:Bar)
  *
  * @property id:string
  * @property bar:Bar
  */
```

limitation:
- can only be applied to __@return__ so far
- the the type must be normal type. Foo(bar:Array[Bar]) is not supported yet
