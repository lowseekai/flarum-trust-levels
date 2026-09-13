import Extend from 'flarum/common/extenders/index';
import adminPage from './components/adminPage';

export default [new Extend.Admin().page(adminPage)];
