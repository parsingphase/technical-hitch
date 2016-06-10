parsingphase/technical-hitch
============================

Open-source version of our wedding website.

Note: this site was written under the sort of time pressure inherent in planning a wedding, and so is not the prettiest
code around. This release is a result of stripping out all the personal information from the site as we made it live. 
It is *not* designed to be an out-of-the box solution for anyone, we're just making the code available in case it's 
useful to anyone with the required technical knowledge (largely because a surprising number of our friends seem to be 
getting married soon, too).

In particular:

- Angular architecture is a bit off (some things should be directives, etc, that aren't)
- Some things should be configuration settings that aren't
- Result checking is not always 100%
- There are no tests (which is a sign I was *really* in a rush!)

Installation
------------
1) Create an empty DB
2) Copy config/parameters.yml.dist to config/parameters.yml and edit
3) Run `ant deploy-current-dev`

You'll need ant and composer installed to use this code. For other deploymen tasks, run `ant -p`. The best way to 
work out what functionality is available is probably just to install the code and experiment. You'll need to give the 
first user you create ROLE_SUPER_ADMIN (see your local symfony expert for advice).

To use the code, you'll probably want to replace the banner image, search/replace the email addresses, search for
"TODO"s, and add your own text on appropriate pages. A lot of this is obviously hard-coded to the way our own wedding 
ran and the options our venue provided.

You'll also need to get an auth token from mapbox.com to configure L.mapbox.accessToken at web/js/maps.js:23
 
While I can't provide formal support for this code, do feel free to drop me a line, open issues or create pull requests.