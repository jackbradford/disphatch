<?php
/**
 * @file
 * This file provides a script used to set cookies related to the catalog
 * search result pages and the admin list pages.
 *
 */


/**
 * SERP Format
 *
 * If cookie is set, but is different to the setting specified in the 
 * request, update the cookie. 
 *
 * If cookie is not set, set it either to the default or to the setting
 * specified in the request at parameter 'lo.'
 */
if (isset($_COOKIE['serp-format'])) {

	if (isset($_GET['lo']) && $_COOKIE['serp-format'] != $_GET['lo']) {

		setLoCookie($_GET['lo']);
	}

} else {

	if (isset($_GET['lo']))	{

		setLoCookie($_GET['lo']);

	} else {
		
		if (!setcookie('serp-format', 'grid', time()+60*60*24*30*2, '/')) {

			// send report that cookie could not be sent.
		}
	}

}

/**
 * SERP item per-page limit
 *
 * If the SERP-limit cookie is set but it doesn't match the specified value,
 * update it.
 *
 * If it's not set, use the default or specified value.
 */
if (isset($_COOKIE['serp-limit'])) {

	if (isset($_GET['limit']) && $_COOKIE['serp-limit'] != $_GET['limit']) {

		if (is_numeric($_GET['limit'])) {
			setLimitCookie($_GET['limit']);
		}
	}

} else {

	if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {

		setLimitCookie($_GET['limit']);

	} else {

		if (!setcookie('serp-limit', 20, time()+60*60*24*30*2, '/')) {

			// send report that cookie could not be sent.
		}
	}

}


/**
 * Admin Cookies
 *
 * For admin requests...
 *
 * If the list-limit cookie is set, update it if needed.
 *
 * If it's not set, use either the default or the value specified in the
 * request to set it.
 */
if (isset($_GET['controller']) && $_GET['controller'] == 'admin') {

	if (isset($_COOKIE['list-limit']))	{

		if (isset($_GET['limit']) && $_COOKIE['list-limit'] != $_GET['limit']) {

			if (is_numeric($_GET['limit'])) {
				setListLimitCookie();
			}

		}

	} else {
		
		if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
			setListLimitCookie();
		} else {
			setListLimitCookie(25);
		}

	}
}


/**
 * Set the "SERP Layout" cookie.
 *
 * @param string $layoutType
 * 	A string denoting the type of layout to display. Accepts "grid" or
 * 	"list." Defaults to "grid."
 *
 * @return
 * 	A cookie is set on the client browser containing the layout type.
 */
function setLoCookie($layoutType)
{

	switch ($layoutType) {

		case 'grid':
			$lo	=	'grid';
			break;
		case 'list':
			$lo =	'list';
			break;
		default:
			$lo =	'grid';
	}

	if (!setcookie('serp-format', $lo, time()+60*60*24*30*2, '/'))
	{
		// send report that cookie could not be sent.
	}
}

/**
 * Set the "Items-Per-Page Limit" cookie.
 *
 * @param int $limit
 * 	An integer representing the number of items to allow on a single
 * 	search-engine result page.
 *
 * @return
 * 	A cookie is set on the client browser containing the number of
 * 	results to allow per SERP.
 */
function setLimitCookie($limit)
{

	if (!setcookie('serp-limit', $limit, time()+60*60*24*30*2, '/')) {

		// send report that cookie could not be sent.
	}

}

/**
 * Set the "Items-Per-Admin-List Limit" cookie.
 *
 * @param int $limit
 * 	An integer representing the number of items to allow in a single
 * 	admin list.
 *
 * @return
 * 	A cookie is set on the client browser containing the directive.
 */
function setListLimitCookie($lim = null)
{
	($lim === null) ? $limit = $_GET['limit'] : $limit = $lim;
	if (!setcookie('list-limit', $limit, time()+60*60*24*30*2, '/'))
	{
		// send report that cookie could not be sent.
		ini_set('error_log', LOGS_PATH . '/app-errors.log');
		error_log('List Limit cookie could not be set.');
	}
}

