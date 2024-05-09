# Nextcloud-Bookmarks-Display
Searchable and collapsible display of browser bookmarks exported from Nextcloud

## Background
I self-host a personal wiki which I use as a private repository of notes, to-dos, and other info. As a matter of curiosity, I wanted to make my brower bookmarks available in this wiki - beyond the basic "export as html" capability. I envisioned a collapsible tree, that was also searchable. My browser bookmarks are synced to an external Nextcloud service via the Floccus extension, and that Nextcloud service exposes the [bookmarks API](https://nextcloud-bookmarks.readthedocs.io/en/latest/index.html) for querying.

## What You'll Need
- PHP
- a scheduler (like cron) to pull bookmarks, run the PHP script, and generate a finished include file

## My Approach
The first step is to run a single script via cron.  This:
- queries the Nextcloud Bookmarks API, which is done in two parts: one query for the folders (`GET /public/rest/v2/folder`) and one query for all bookmarks (`GET /public/rest/v2/bookmark`)
- runs the main PHP script, which reads in the folder/bookmark files and generates a finished include file

The core work is done in PHP. This script generates the hierarchy of bookmarks into a valid HTML structure of UL/LI. There's nothing ground-breaking about it, but a few things worth mentioning:
- the Nextcloud Bookmarks API output isn't great:
  - as far as I can tell, beyond an HTML export there isn't a good way to simply pull down "everything" - folders and bookmarks together, in some parseable structure. Hence the need to query folders and bookmarks separately
  - each folder in the folders file has an id, and each bookmark in the bookmarks file has an id - but those ids are **not** unique when combined. This caused problems when trying to generate the parent/child hierarchy, so there's logic to accommodate that
  - hierarchy generation is typical recursive stuff, but with some subtleties to account for generating valid UL/LI markup
- the tree structure and searching are done via [hakoiko's](https://github.com/hakoiko) [jQuery Tree Filter](https://github.com/hakoiko/jquery-tree-filter).
- the PHP script will generate a finished HTML file which can then be used as an include (for me, into a page in my wiki). References/includes to CSS and JS files are self-contained, but verify paths as necessary.

## Usage
Create a shell script to run via cron:
```
#!/bin/sh
cd /path/to/working/dir
curl -s -u '[credentials]' -H "Accept: application/json" https://[your.nextcloud]/index.php/apps/bookmarks/public/rest/v2/folder --output thegood.bkfolders.json
curl -s -u '[credentials]' -H "Accept: application/json" https://[your.nextcloud]/index.php/apps/bookmarks/public/rest/v2/bookmark?limit=1000 --output thegood.bks.json
php bookmarks.php > bookmarks.inc
```

Include `bookmarks.inc` into your site.
