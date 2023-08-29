> IMPORTANT: This code is made available in the hope that it will be useful, but **without any warranty**. See the GNU General Public License included with the code for more details. Automattic or WooCommerce support services are also not available to assist with the use of this code.

## WooCommerce Subscriptions Recalculate Subscription Totals

In some cases, if the tax settings change after some subscriptions have been created, their totals need to be recalculated in order to include the proper taxes. This plugin recalculates all the subscriptions totals.

To run the plugin, add `?wcs-recalculate-totals=true` to any admin URL.
- Add `&readd` parameter (`?wcs-recalculate-totals=true&readd=true`) to force the plugin to delete all line items of the order and add them again before recalculating the totals (this helps in case tax settings have changed and the tax it not included properly just by recalculating the order totals)

For each iteration of the fixer's code, a log entry will be added to a log file prefixed with `'wcs-recalculate-totals'`. To view this log file, visit **WooCommerce > System Status > Logs**.

### Important

This is an experimental extension and it hasn't been tested in all scenarios. Please **backup your database before running it** and **try it first on a staging/development version of your site** (confirm that all the products have been added again with the correct prices, taxes, shipping, etc) before running it on a production site. 

### How to update the same subscriptions again?
All the subscripitons that have been already updated by the plugin will not be processed a second time because their ID is stored in the `wcs_subscriptions_with_totals_updated` option. If you want to reset it to be able to process all your subscriptions again, you'll need to add the `?reset-updated-subs-option=true` parameter to the URL. 

### Installation

1. Upload the plugin's files to the `/wp-content/plugins/` directory of your WordPress site
1. Activate the plugin through the **Plugins** menu in WordPress


#### License

This plugin is released under [GNU General Public License v3.0](http://www.gnu.org/licenses/gpl-3.0.html).

---

<p align="center">
<img src="https://cloud.githubusercontent.com/assets/235523/11986380/bb6a0958-a983-11e5-8e9b-b9781d37c64a.png" width="160">
</p>
