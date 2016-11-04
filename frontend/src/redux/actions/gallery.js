var Request = require('libs/request');

function loadStarted() {
  return {
    type: 'GALLERY_LOAD_STARTED',
  }
}

function loadSuccess(response, lang, tag) {
  return {
    type: 'GALLERY_LOAD_SUCCESS',
    lang,
    response,
    tag,
  }
}

function loadError(error, lang, tag) {
  return {
    type: 'GALLERY_LOAD_ERROR',
    error,
    lang,
    tag,
  }
}

export function loadGallery(page, tag) {
  return (dispatch, getState) => {
    var lang = getState().lang;

    dispatch(loadStarted());

    return new Promise((resolve, reject) => {
      Request.fetch(
        '/api/gallery/getPhotos/', {
        success: response => {
          dispatch(loadSuccess(response, lang, tag));
          resolve();
        },

        error: error => {
          dispatch(loadError(error, lang, tag));
          resolve();
        },

        data: {
          USER_LANG: lang,
          tag,
          page,
        },

        cache:        true,
        cache_expire: 3600,
      });
    })
  }
}