<style>
<? include('treeview/dist/jquery.treefilter.css'); ?>
</style>
    
<input type="search" id="my-search" placeholder="search">

<?
date_default_timezone_set('America/New_York');

$bkFile = '/path/to/bks.json';
$bks = file_get_contents($bkFile);

$folderFile = '/path/to/bkfolders.json';
$folders = file_get_contents($folderFile);

// read in folders json (with hierarchy), output flat array
function foldersToArray($arr, $flatFolderArray) {
    global $flatFolderArray;
    
    foreach ($arr as $value) {
        $bkItem = array();
        $bkItem['id'] = $value->id;
        $bkItem['title'] = $value->title;
        $bkItem['url'] = '';
        $bkItem['parent'] = $value->parent_folder;
        $bkItem['type'] = 'folder';
        array_push($flatFolderArray, $bkItem);
        
        // recursive call since input is in nested hierarchy
        foldersToArray($value->children, $flatFolderArray);
    }
}

// read in flat folder array, output list of just folder ids
function createFolderIdList($flatFolderArray) {
    $result = array();
    foreach ($flatFolderArray as $folder) {
        array_push($result, $folder['id']);
    }
    return $result;
}

// read in bookmarks json, output array with modified ids
function bookmarksToArray($bkArr, $folderIdList) {
    $resultArr = array();

    // get current max bookmark id
    $currentMax = max(array_column($bkArr, 'id'));

    foreach ($bkArr as $bk) {
        $bkItem = array();

        // some bookmark ids are the same as folder ids
        // that screws up recursion later with nested hierarchy
        // so assign new ids whenever there's a conflict
        $bkNewId = $bk->id;
        if (array_search($bkNewId, $folderIdList) !== false) {
            $bkNewId = $currentMax++;
        }
        $bkItem['id'] = $bkNewId;

        $bkItem['title'] = $bk->title;
        $bkItem['url'] = $bk->url;
        $bkItem['parent'] = $bk->folders[0];
        $bkItem['type'] = 'bookmark';
        array_push($resultArr, $bkItem);
    }
    
    return $resultArr;
}

// traverse all data (folders and bookmarks), prepare UL/LI with correct nesting
function recursive($parent, $array, $fullContent) {
    global $fullContent;
    
    $has_children = false;
    foreach($array as $value) {
        if ($value['parent'] == $parent) {
            if ($has_children === false && $parent) {
                $has_children = true;
                $fullContent .= '<ul>' ."\n";
            }
            $fullContent .= '<li>';
            if ($value['type'] == 'bookmark') {
                $fullContent .= '<div><a href="' . $value['url'] . '">' . $value['title'] . '</a></div>';
            } else {
                $fullContent .= '<div>' . $value['title'] . '</div>';
            }
            recursive($value['id'], $array, $fullContent);
            $fullContent .= "</li>\n";
        }
    }
    if ($has_children === true && $parent) $fullContent .= "</ul>\n";
}

// main method, if bookmarks and folders files exist
if (file_exists($bkFile) && file_exists($folderFile)) {
    // folders
    $folderObj = json_decode($folders);
    $folderArr = $folderObj->data;
    
    $flatFolderArray = array();
    foldersToArray($folderArr, $flatFolderArray);
    $folderIdList = createFolderIdList($flatFolderArray);

    // bookmarks
    $bkObj = json_decode($bks);
    $bkArr = $bkObj->data;

    $resultArray = bookmarksToArray($bkArr, $folderIdList);

    // merge bookmarks and folders arrays
    $finalArray = array_merge($resultArray, $flatFolderArray);

    $fullContent = '';
    recursive(-1, $finalArray, $fullContent);

    // first UL needs to have this css id for jquery tree
    echo '<ul id="my-tree">' . "\n";
    // trim the redundant first UL from recursive function
    echo substr($fullContent, strpos($fullContent, "\n") + 1);
} else {
    echo 'Error retrieving bookmarks and/or folders.';
}

?>

<p style="margin-top:2em;"><span style="font-size:0.8em;color:#999;">Bookmark file as of <? echo date("n-j-Y, g:i a", filemtime($bkFile)); ?></span></p>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="/treeview/dist/jquery.treefilter-0.1.0.js"></script>

<script>
$(function() {
    var tree = new treefilter($("#my-tree"), {
        // OPTIONS
        offsetLeft : 20, 
        expanded : false, 
        multiselect : false,
        searcher : $("input#my-search")
    });
});
</script>
