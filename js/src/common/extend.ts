import Extend from 'flarum/common/extenders';
import Condition from './models/Condition';
import CustomCondition from './models/CustomCondition';
export default [
    new Extend.Store()
        .add('condition', Condition)
        .add(CustomCondition.type, CustomCondition)
];