<?php
/**
 *  Feed updater
 */
class Feed_CliController extends CliController {
  public function actionDefault() {
    return $this->actionUpdate();
  }

  /**
   *  Update feed
   */
  public function actionUpdate() {
    $feed = array_merge(
      $this->_getPages(),
      $this->_getPhotos()
    );

    $this->_clearFeed($feed);
    $this->_updateFeed($feed);
  }

  /**
   *  Clear outdated rows
   */
  protected function _clearFeed($feed) {
    $uniq = array();

    foreach ($feed as $row) {
      $uniq[$row['type'].'-'.$row['id']] = true;
    }

    foreach (Feed::get() as $row) {
      if (isset($uniq[$row->feed_type.'-'.$row->feed_target_id])) {
        continue;
      }

      Tags::attachTags('feed', $row->feed_id, array());
      $row->delete();
    }
  }

  /**
   *  Update/insert rows
   */
  protected function _updateFeed($feed) {
    foreach ($feed as $row) {
      $res = Feed::where('feed_type', '=', $row['type']);
      $res->whereAnd('feed_target_id', '=', $row['id']);

      if (count($res = $res->get())) {
        $res = $res[0];
      }
      else {
        $res = new Feed;
        $res->feed_type      = $row['type'];
        $res->feed_target_id = $row['id'];
      }

      $res->feed_title_ru = $row['title_ru'];
      $res->feed_title_en = $row['title_en'];
      $res->feed_preview  = $row['preview'];
      $res->feed_ratio    = $row['ratio'];
      $res->feed_order    = $row['order'];

      $res->save();

      $tags = array();

      foreach (explode(',', $row['tags']) as $tag) {
        if (!($tag = trim($tag))) {
          continue;
        }

        $tags[] = $tag;
      }

      Tags::attachTags(
        'feed',
        $res->feed_id,
        $tags,
        $row['type'] == 'gallery' ? 1 : 5
      );
    }
  }

  /**
   *  Get pages
   */
  protected function _getPages() {
    $ret = array();

    $res = MediaPages::where('page_visible', '=', '1');
    $res->whereAnd('page_deleted', '=', '0');

    foreach ($res->get() as $page) {
      $info = array(
        'id'        => $page->page_id,
        'type'      => $page->page_type,
        'title_ru'  => '',
        'title_en'  => '',
        'preview'   => '',
        'ratio'     => 3,
        'tags'      => $page->page_tags,
        'order'     => $page->page_date_timestamp ? $page->page_date_timestamp : $page->page_id,
      );

      if ($page->page_photo_id && $preview = $this->_getPagePreview($page->page_photo_id)) {
        $info['preview'] = $preview;
      }

      $entry_id = Database::from('media_entries');
      $entry_id->where('entry_key', '=', 'page_' . $page->page_id);

      if (!count($entry_id = $entry_id->get())) {
        return $entry_id;
      }

      $entry_id = $entry_id[0]->entry_id;

      $entries = Database::from('media_entries_content');
      $entries->whereAnd('entry_id', '=', $entry_id);
      $entries->whereAnd('entry_visible', '=', '1');
      $entries->whereAnd('entry_lang', 'IN', array('ru', 'en'));

      foreach ($entries->get() as $entry) {
        $info['title_' . $entry->entry_lang] = $entry->entry_title;
      }

      $ret[] = $info;
    }

    return $ret;
  }

  /**
   *  Get photos
   */
  protected function _getPhotos() {
    $ret = array();

    $res = Database::from('photolibrary_collections');
    $res->whereAnd('collection_name', 'LIKE', 'gallery');

    if (!count($res = $res->get())) {
      return $ret;
    }

    $collection = $res[0]->collection_id;

    $res = Photolibrary::where('photo_collection_id', '=', $collection);
    $res->whereAnd('photo_deleted', '=', '0');

    foreach ($res->get() as $photo) {
      $info = array(
        'id'        => $photo->file_id,
        'type'      => 'gallery',
        'title_ru'  => $photo->photo_title_ru,
        'title_en'  => $photo->photo_title_en,
        'preview'   => '',
        'ratio'     => 1,
        'order'     => $photo->photo_taken_timestamp ? $photo->photo_taken_timestamp : $photo->file_id,
        'tags'      => implode(',', array(
          $photo->photo_lens,
          $photo->photo_camera,
          $photo->photo_category,
        )),
      );

      if (preg_match('#^(\d++)x(\d++)$#', $photo->photo_size, $data)) {
        $info['ratio'] = round((double)$data[1] / (double)$data[2], 1);
      }

      $w = 500;
      $h = 200;

      if ($info['ratio'] < 1) {
        $w = 500;
        $h = 500;
      }

      if ($info['ratio'] > 2) {
        $w = 900;
        $h = 200;
      }

      $info['preview'] = StoragePreview::makePreviewLink(
        $photo->file_hash, array(
          'crop'   => true,
          'width'  => $w,
          'height' => $h,
          'align'  => 'center',
          'valign' => 'middle',
        )
      );

      $ret[] = $info;
    }

    return $ret;
  }

  /**
   *  Get page preview
   */
  protected function _getPagePreview($photo_id) {
    if (!($photo = Photolibrary::find($photo_id))) {
      return false;
    }

    if ($photo->photo_deleted) {
      return false;
    }

    return StoragePreview::makePreviewLink(
      $photo->file_hash, array(
        'crop'   => true,
        'width'  => 900,
        'height' => 200,
        'align'  => 'center',
        'valign' => 'middle',
      )
    );
  }
}
?>