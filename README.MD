So, this class does a lot of fancy reporting using the following:

* MySQL for finding the ranking data
* Google Adwords API for interactions with AdWords
* Google Analytics API for interactions with Analytics
* FPDF for making the fancy reports

It takes information from:

* Google Analytics
* Google AdWords
* Advanced Web Rankings CSVs (which get processed by another script that sits at an email address)

Then it pulls all the data together and makes an awesome looking report.

As this has been taken straight out of the CRM module, it won't work too well on its own.

In the CRM module, these are all actually tied together by a master control script that gets activated due to a CRON.

