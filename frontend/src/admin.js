var React       = require('react');
var ReactDOM    = require('react-dom');
var ReactRouter = require('react-router');
var onready     = require('libs/onready');
var Lang        = require('libs/lang');

var {
  Router,
  Route,
  IndexRoute,
  browserHistory
} = require('react-router');

var Photos      = require('components/admin/route-photos');
var Storage     = require('components/admin/route-storage');
var PageEditor  = require('components/admin/route-page-editor');
var PagesList   = require('components/admin/route-pages-list');
var Menu        = require('components/admin/view-navigation');
var Footer      = require('components/admin/view-footer');
var Progress    = require('components/shared/request-progress');

require('styles/admin.scss');

var App = React.createClass({
  render() {
    return (
      <div>
        <Menu />
        <Progress />

        {this.props.children ? React.cloneElement(this.props.children, this.props) : null}

        <Footer />
      </div>
    );
  },
});

var NotFound = React.createClass({
  componentWillMount() {
    browserHistory.push('/'+Lang.getLang()+'/admin/storage/');
  },

  render() {
    return null;
  }
});

onready(() => {
  ReactDOM.render(
    <Router history={browserHistory}>

      <Route path={'ru/admin/'} component={App}>
        <IndexRoute component={Storage} />

        <Route path='photos/' component={Photos} />
        <Route path='pages/:page_type' component={PagesList} />
        <Route path='pages/:page_type/:page_id' component={PageEditor} />
        <Route path='storage' component={Storage} />
        <Route path='*' component={Storage} />
      </Route>

      <Route path={'en/admin/'} component={App}>
        <IndexRoute component={Storage} />

        <Route path='photos/' component={Photos} />
        <Route path='pages/:page_type' component={PagesList} />
        <Route path='pages/:page_type/:page_id' component={PageEditor} />
        <Route path='storage' component={Storage} />
        <Route path='*' component={Storage} />
      </Route>
      
      <Route path='*' component={NotFound} />
    </Router>,
    document.getElementById('react-root')
  );
});