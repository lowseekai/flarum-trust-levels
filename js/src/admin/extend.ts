import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders/index';
import commonExtend from '../common/extend';
import adminPage from './components/adminPage';

export default [
  ...commonExtend,

  new Extend.Admin()
    .page(adminPage)
    .permission(
      () => ({
        icon: 'fas fa-eye',
        label: app.translator.trans('xypp-collector.admin.permissions.view-condition'),
        permission: 'user.view-condition',
      }),
      'moderate',
      30
    ),
];
