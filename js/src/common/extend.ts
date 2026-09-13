import Extend from 'flarum/common/extenders/index';
import TrustLevel from './models/TrustLevel';
export default [
    new Extend.Store()
        .add('trust-levels', TrustLevel)
];
