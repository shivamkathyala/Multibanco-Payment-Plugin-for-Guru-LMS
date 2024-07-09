# Multibanco-Payment-Plugin-for-Guru-LMS

The Multibanco Payment Processor Plugin integrates Multibanco, a popular payment method in Portugal provided by Ifthenpay, into the Guru LMS platform. Once installed, this plugin adds Multibanco as a payment option on the cart page and allows for various configurations from the plugin settings page.

## Features

1. **Automatic Email Notification**: When the Multibanco reference is generated, an email with the reference template is automatically sent to the user.
2. **Sandbox Mode**: You can set the sandbox account for testing the plugin from the plugin settings page.
3. **Approved Callback URL**: The plugin provides an approved callback URL for updating payment status.
4. **Dynamic Keys**: Dynamically set the MB key and phishing key from the plugin settings.
5. **Mobile-Responsive**: The plugin is fully responsive and works seamlessly on mobile devices.
6. **Multilingual Support**: Supports both English and Portuguese languages.

## Installation

1. Download the latest release of the plugin from the (https://github.com/shivamkathyala/Multibanco-Payment-Plugin-for-Guru-LMS).
2. In your Joomla backend, go to `Extensions` > `Manage` > `Install`.
3. Upload the plugin zip file and click `Upload & Install`.
4. Once installed, go to `Extensions` > `Plugins` and search for `Multibanco`. Enable the plugin.

## Configuration

1. Navigate to `Extensions` > `Plugins` and find the `Multibanco` plugin.
2. Click on the plugin name to open the configuration page.
3. Fill in the required fields:
   - **Multibanco Label**: Enter a label for the payment method.
   - **MB Key**: Enter your Multibanco key provided by Ifthenpay.
   - **Phishing Key**: Enter your phishing key provided by Ifthenpay.
   - **Sandbox Mode**: Choose whether to enable sandbox mode for testing.
   - **Expiration Date**: Set the expiration date for Multibanco references.
4. Copy the approved callback URL and phishing key from the plugin settings and add them to your Ifthenpay back office.

## Support

For any issues or feature requests, please contact the author.

