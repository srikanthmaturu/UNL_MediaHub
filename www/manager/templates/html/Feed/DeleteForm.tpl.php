<form id="deleteForm" action="<?php echo UNL_MediaHub_Manager::getURL() ?>" method="post" class="confirm-delete">
    <input type="hidden" name="__unlmy_posttarget" value="delete_feed" />
    <input type="hidden" name="feed_id" value="<?php echo $context->id; ?>" />
    <button type="submit" class="delete wdn-button wdn-button-brand">Delete</button>
</form>
