import Extend from 'flarum/common/extenders';
import adminPage from './components/adminPage';

export default [new Extend.Admin().page(adminPage)];
