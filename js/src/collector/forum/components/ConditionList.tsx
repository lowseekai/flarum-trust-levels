import app from 'flarum/forum/app';
import Component from "flarum/common/Component";
import { ConditionData, OPERATOR } from "../../common/types/data";
import Condition from "../../common/models/Condition";
import HumanizeUtils from '../../common/utils/HumanizeUtils';
import userValueUtil from '../utils/userValueUtil';

export default class ConditionList extends Component<{
    conditions: ConditionData[],
    conditionMap?: Record<string, Condition>
}> {
    view(vnode: any) {
        const humanize = HumanizeUtils.getInstance(app);

        return <div className='collector-condition'>
            <div className='collector-condition-title'><i class="fas fa-tasks"></i>{" "}{app.translator.trans("xypp-collector.forum.condition.condition")}</div>
            {
                this.attrs.conditions.map(condition => {
                    return <div className='collector-condition-line'><span>{humanize.humanizeCondition(condition)}</span>
                        {this.progress(condition, humanize)}
                    </div>;
                })
            }
        </div>
    }
    progress(condition: ConditionData, humanize: HumanizeUtils) {
        if (!this.attrs.conditionMap) return "";
        const valueHelper = new userValueUtil(humanize, this.attrs.conditionMap);
        let value = valueHelper.getValue(condition);
        const satisfy = this.conditionOp(value, condition.operator, condition.value);
        if (satisfy) {
            return <span className="collector-progress-satisfy">
                [<i class="fas fa-check"></i>]
            </span>
        }
        return (
            <span className={"collector-progress-not-satisfy"}>
                [{value}/{condition.value}]
            </span>
        );
    }
    conditionOp(value1: number, op: OPERATOR, value2: number) {
        switch (op) {
            case OPERATOR.EQUAL:
                return value1 == value2;
            case OPERATOR.GREATER_THAN:
                return value1 > value2;
            case OPERATOR.GREATER_THAN_OR_EQUAL:
                return value1 >= value2;
            case OPERATOR.LESS_THAN:
                return value1 < value2;
            case OPERATOR.LESS_THAN_OR_EQUAL:
                return value1 <= value2;
            case OPERATOR.NOT_EQUAL:
                return value1 != value2;
        }
    }

}