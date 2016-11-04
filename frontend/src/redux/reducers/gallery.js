var clone = require('libs/clone');

var initialState = {
  photos:  [],
  tag:     '',
  page:    false,
  pages:   false,
  lang:    false,
  loaded:  false,
  loading: false,
  error:   false,
};

module.exports = function(state = initialState, action) {
  switch (action.type) {
    case 'GALLERY_LOAD_STARTED': {
      state = clone(state);

      state.photos  = [];
      state.error   = false;
      state.loading = true;
      state.loaded  = false;

      return state;
    }

    case 'GALLERY_LOAD_ERROR': {
      state = clone(state);

      state.error   = action.error;
      state.loading = false;
      state.loaded  = true;
      state.lang    = action.lang;
      state.tag     = action.tag;

      return state;
    }

    case 'GALLERY_LOAD_SUCCESS': {
      state = clone(state);

      state.tag     = action.tag;
      state.photos  = action.response.photos;
      state.page    = action.response.page;
      state.pages   = action.response.pages;
      state.error   = false;
      state.loading = false;
      state.loaded  = true;
      state.lang    = action.lang;

      return state;
    }

    default: {
      return state;
    }
  }
}