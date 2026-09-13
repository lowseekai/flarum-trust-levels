import HumanizeUtils from "../../common/utils/HumanizeUtils";
import Condition from "../../common/models/Condition";
import { ConditionData } from "../../common/types/data";
export default class userValueUtil {
    private humanize;
    private conditionMap;
    constructor(humanize: HumanizeUtils, conditionMap?: Record<string, Condition>);
    load(): Promise<void>;
    parse(evaluation: string, data: ConditionData): false | number;
    getValue(condition: ConditionData): number;
}
