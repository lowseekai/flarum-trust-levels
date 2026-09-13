import app from 'flarum/forum/app';
import { init } from '../common/integration';
import { registerCount } from './integration/pageCount';
app.initializers.add('xypp/collector', () => {
  init(app, "forum");
  registerCount();
});

import { addCondition, addReward, addRewardSelection, rewardValueConvert } from '../common/utils/AddFrontend';
import { triggerCondition, triggerConditions } from './utils/frontendTrigger';
import HumanizeUtils from '../common/utils/HumanizeUtils';
import Condition from '../common/models/Condition';
import { getConditionMap, getConditions } from './utils/userCondition';
import ConditionList from './components/ConditionList';
import RewardList from './components/RewardList';
import userValueUtil from './utils/userValueUtil';
import { OPERATOR,CALCULATE } from '../common/types/data';
export {
  addCondition,
  addReward,
  addRewardSelection,
  rewardValueConvert,
  triggerCondition,
  triggerConditions,
  HumanizeUtils,
  Condition,
  getConditionMap,
  getConditions,
  ConditionList,
  RewardList,
  userValueUtil,
  OPERATOR,
  CALCULATE
};
