<?php
$fileId = get_query_var('fileid');
$oldSecret = get_query_var('secret');

// Handle KEY Request
if ( $fileId && $oldSecret ) {

    //$postId = getPostId($fileId);
    $ID = getPostId($fileId);


    if ($ID) {
        $postID     = getPostMeta($ID, 'post_id');
        $refererReq = getPostMeta($ID, 'referer_req');

        $newSecret = md5( get_option( 'salt_str' ) . getUserIP() . $_SERVER['HTTP_REFERER'] );

        if( $refererReq != 'yes' || ($oldSecret == $newSecret) ) {

            $created = getPostMeta($ID, 'created');

            if( checkExpiration($created) ) {

                ob_clean();
                ob_start();

                // Get File Name
                $fileName = basename(get_attached_file($postID));

                // Get the file content
                $strFile = file_get_contents(get_the_guid($postID));

                //set the headers to force a download
                header('Content-type: application/force-download');
                header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $fileName) . '"');

                // echo the file to the user
                echo $strFile;

                ob_end_flush();

            } else {
                echo 'Expired.';
            }

        } else {
            echo 'Access denied.';
        }

    } else {
        echo 'File Missing.';
    };

} else {
    echo 'Incorrect URL.';
}

exit;