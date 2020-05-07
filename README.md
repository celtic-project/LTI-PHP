This set of PHP classes encapsulates the code required by an LTI compliant tool provider to communicate with an LTI tool consumer.
It includes support for LTI 1.1 and the unofficial extensions to Basic LTI, as well as the registration process and services of LTI 1.2/2.0.
Support has also been added for the Names and Role Provisioning service and the Result and Score services where these are supported using the
OAuth 1 security model (support for the new security model in LTI 1.3 will be in a forthcoming update).
These classes are designed as an update to the LTI Tool Provider class library (http://www.spvsoftwareproducts.com/php/lti_tool_provider/) and
a replacement for the library at https://github.com/IMSGlobal/LTI-Tool-Provider-Library-PHP which is no longer supported.

Whilst supporting LTI is relatively simple, the benefits to using a class library like this one are:
* the abstraction layer provided by the classes keeps the LTI communications separate from the application code;
* the code can be re-used between multiple tool providers;
* LTI data is transformed into useful objects and missing data automatically replaced with sensible defaults;
* the outcomes service function uses LTI 1.1 or the unofficial outcomes extension according to whichever is supported by the tool consumer;
* the unofficial extensions for memberships and setting services are supported;
* additional functionality is included to:
    * enable/disable a consumer key;
    * set start and end times for enabling access for each consumer key;
    * set up arrangements such that users from different resource links can all collaborate together within a single tool provider link;
* LTI applications can take advantage of LTI updates with minimal impact on their code.

The GitHub repository provides access to the [source files](https://github.com/celtic-project/LTI-PHP) and [documentation](https://github.com/celtic-project/LTI-PHP/wiki).
The example [Rating LTI application](https://github.com/celtic-project/Rating-PHP) is based on this library to further illustrate how it can be used.
