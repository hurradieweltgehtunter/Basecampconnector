# Basecampconnector
 
This Wordpress plugin connects your website with your Basecamp account. 
See the Basecamp API docs: https://github.com/basecamp/bc3-api

Note: The underlying Basecamp PHP SDK https://github.com/arturf/basecamp-api is not up-to-date completely using the Basecamp V3 Api. See folder `includes/Basecamp` for updated files.Since this is a very specific project repo for me I did not update the whole SDK. Also there are many project specific actions and settings included which makes this repo/plugin not usable as it is for other websites.


# Installation
- Upload to plugins dir
- execute `composer install`
- Login to Wordpress
- See "Basecamp Connector" menu entry in the left menu sidebar for settings.


# Concept
This plugin uses a standard basecamp user account to interact with Basecamp. See the bottom of the "Basecamp Connector" settings page in Wordpress for details how to authenticate the plugin.
Note: If the Connector should interact with projects (Post messages, etc.) you must ensure that the used Basecamp account has already access to this projects. Otherwise the interaction will fail.

# ToDo
- Add a logging mechanism for all executed tasks
- Add uninstall routine

# Disclaimer
This plugin is very specific to a project I am working on. It's goal is not to provide an as-it-is Wordpress plugin and is aimed for developers. Please feel free to open issues or contact me if you have questions, feature requests or ther ideas.