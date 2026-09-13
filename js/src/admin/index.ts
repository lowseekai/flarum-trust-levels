import app from 'flarum/admin/app';
import { init } from '../collector/common/integration';

export { default as extend } from './extend';

app.initializers.add('xypp/flarum-trust-levels', () => {
  init(app, 'admin');
});
