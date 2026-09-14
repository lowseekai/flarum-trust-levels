import Model from 'flarum/common/Model';
import type { ConditionData } from '../../collector/common/types/data';


export default class TrustLevel extends Model {
  name = Model.attribute<string>('name');
  condition = Model.attribute<ConditionData[]>('conditions');
  icon = Model.attribute<string | null>('icon');
  level = Model.attribute<number>('level');
  group_id = Model.attribute<number | null>('group_id');
  allow_downgrade = Model.attribute<boolean>('allow_downgrade');
  downgrade_grace_days = Model.attribute<number>('downgrade_grace_days');
  manual_only = Model.attribute<boolean>('manual_only');
  next = Model.hasOne<TrustLevel>('next');
}
