### About
This repository houses an Omeka plugin that allows users to protect restricted routes of an application with CAS authentication.

### Installation

```
# download the source within your omeka install's plugins directory
git clone https://github.com/YaleDHLab/omeka-plugin-Casify
mv omeka-plugin-Casify Casify

# identify the collection ids you want to protect in line 16 of libraries/Casify_ControllerPlugin.php 
# identify your Omeka database credentials in lines 30 and 31 of libraries/Casify_ControllerPlugin.php
# identify your CAS endpoint in line 96 of libraries/Casify_ControllerPlugin.php 
```

If you then visit your omeka install's `/admin` dashboard, you should be able to install the plugin. After doing so, visiting a protected route will send users to your CAS endpoint.
