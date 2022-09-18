# todoist_display

A small PHP script that display a todolist from todoist in a Yocto-MaxiDisplay. See the full article on our web site: https://www.yoctopuce.com/EN/article/display-your-todoist-tasks-on-a-yocto-maxidisplay


## Installation

To install this script on your server, you must copy the files in your directory and edit the <tt>index.php</tt> file to update the following three variables:
````php
const CALLBACK_MD5_PASS = "change_this";
const TODOIST_API_KEY = "REPLACE WITH YOUR API KEY";
const TODOIST_PROJECT = "";
````
The ``CALLBACK_MD5_PASS`` variable corresponds to the password used for the MD5 signature. ``TODOIST_API_KEY`` is the Todoist authentication key. Finally, ``TODOIST_PROJECT`` enables you to filter tasks to use only those of a specific project. If this value is an empty string, the tasks of all the projects are displayed.

For the HTTP callback mode to work, the PHP option allow_url_fopen must be enabled. If this option is disabled, the ``YAPI::RegisterHub`` returns a "URL file-access is disabled in the server configuration" error. In this case, you can consult this article: https://www.yoctopuce.com/EN/article/using-the-http-callback-mode-in-php

