/// <reference types="mithril" />
import Component from "flarum/common/Component";
import { ConditionData, OPERATOR } from "../../common/types/data";
import Condition from "../../common/models/Condition";
import HumanizeUtils from '../../common/utils/HumanizeUtils';
export default class ConditionList extends Component<{
    conditions: ConditionData[];
    conditionMap?: Record<string, Condition>;
}> {
    view(vnode: any): JSX.Element;
    progress(condition: ConditionData, humanize: HumanizeUtils): "" | JSX.Element;
    conditionOp(value1: number, op: OPERATOR, value2: number): boolean;
}
