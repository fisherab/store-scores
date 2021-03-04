# Overview
This is intended to provide functions to allow an admin to:

* create an event with a specific set of rules for determining the winner
* for a user to register for an event
* for a user to enter their own scores and see the results so far

# Installation

1. Upload `store-scores.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

# Use
1. Create a new competition with for example a title of "fred" and add the contestants (you choose from the current set of members)
1. Create a post with the shortcode [ss-enter-score competition="fred"] where 
1. Logged in visitors to that page can look up their competitor and enter the result
