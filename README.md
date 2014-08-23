Wordpress Amazon SNS
====================

* Publish messages from your contact forms to an SNS topic on Amazon

Uses the <a href="https://github.com/aws/aws-sdk-php">Amazon SDK for PHP</a> to connect to and publish messages to SNS.

Usage
=====

Create SNS topic and subscribe your email address to it.
Create AWS IAM credentials and give permission it LIST and PUBLISH permission to SNS topic(s).
Install plugin into your wordpress.
Configure all the options.
Add a html form to a page as per the example on the plugin settings page.

TODO
====
General validation of settings... ATM things break when they are not configured.
Test AWS Credentials on Settings Page, disable plugin until settings are valid.
Get list of available topics rather then manual entry.
Get list of available regions rather then manual entry.
