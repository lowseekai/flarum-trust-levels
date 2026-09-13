import Extend from 'flarum/common/extenders/index';
import TrustLevel from './models/TrustLevel';
import collectorExtend from '../collector/common/extend';

export default [
  new Extend.Store()
    .add('trust-levels', TrustLevel),

  ...collectorExtend,
];
