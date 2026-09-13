import Model from 'flarum/common/Model';
export default class CustomCondition extends Model {
  static type = 'custom-condition';
  name = Model.attribute<string>('name');
  display_name = Model.attribute<string>('display_name');
  evaluation = Model.attribute<string>('evaluation');
}