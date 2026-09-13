import Model from 'flarum/common/Model';
import type { ConditionData } from '@xypp-collector/common/types/data';


export default class TrustLevel extends Model {
  name = Model.attribute<string>('name');
  condition = Model.attribute<ConditionData[]>('conditions');
  icon = Model.attribute<string | null>('icon');
  level = Model.attribute<number>('level');
  group_id = Model.attribute<number | null>('group_id');
  next = Model.hasOne<TrustLevel>('next');
}
