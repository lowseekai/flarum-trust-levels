import Model from 'flarum/common/Model';
export default class CustomCondition extends Model {
    static type: string;
    name: () => string;
    display_name: () => string;
    evaluation: () => string;
}
