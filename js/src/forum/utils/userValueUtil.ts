import { getConditionMap } from "./userCondition";
import HumanizeUtils from "../../common/utils/HumanizeUtils";
import { evaluate } from 'decimal-eval';
import Condition from "../../common/models/Condition";
import { ConditionData } from "../../common/types/data";

export default class userValueUtil {
    private humanize;
    private conditionMap: Record<string, Condition> = {};
    constructor(humanize: HumanizeUtils, conditionMap?: Record<string, Condition>) {
        this.humanize = humanize;
        if (conditionMap) this.conditionMap = conditionMap;
    }
    async load() {
        this.conditionMap = await getConditionMap();
    }
    parse(evaluation: string, data: ConditionData): false | number {
        if (!this.conditionMap) return false;
        const match = evaluation.matchAll(/\{([^\}]*)\}/ig);
        for (const m of match) {
            const condition = this.conditionMap[m[1]];
            if (!condition) return false;
            let value: number = condition.getTotal(data.calculate);
            if (data.span) {
                value = condition.getSpan(data.span, data.calculate);
            }
            evaluation = evaluation.replace(m[0], value + "");
        }
        try {
            return Math.floor(parseFloat(evaluate(evaluation)));
        } catch (e) {
            return false;
        }
    }

    getValue(condition: ConditionData): number {
        const def = this.humanize.getRawConditionDefinition(condition.name);
        if (!def) return 0;
        if (!def.evaluation) {
            const users = this.conditionMap[condition.name];
            if (!users) return 0;
            let value: number = users.getTotal(condition.calculate);
            if (condition.span) {
                value = users.getSpan(condition.span, condition.calculate);
            }
            return value;
        }
        return this.parse(def.evaluation, condition) || 0;
    }
}
