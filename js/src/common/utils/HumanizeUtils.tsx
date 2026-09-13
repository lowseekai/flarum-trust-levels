import ForumApplication from "flarum/forum/ForumApplication";
import AdminApplication from "flarum/admin/AdminApplication";

import { CALCULATE, ConditionData, RewardData } from "../types/data";
import ItemList from "flarum/common/utils/ItemList";
import Condition from "../models/Condition";
import { showIf } from "./NodeUtil";

const CONDITION_OPERATOR_MAP = {
    ">=":"ge",
    ">":"gt",
    "<=":"le",
    "<":"lt",
    "=":"eq",
    "!=":"ne"
}

export default class HumanizeUtils {
    public static instance?: HumanizeUtils;
    protected app: ForumApplication | AdminApplication;
    protected definitionLoaded: boolean = false;
    protected rawConditionDefinition: Record<string, {
        global: boolean, trans: string, key: string, manual: boolean, abs: boolean, update: boolean, evaluation?: string
    }> = {}
    protected conditionTranslations: Record<string, string> = {};
    protected rewardTranslations: Record<string, string> = {};
    protected conditionsKeys: string[] = [];
    protected rewardsKeys: string[] = [];
    constructor(app: ForumApplication | AdminApplication) {
        this.app = app;
    }
    public async loadDefinition() {
        if (this.definitionLoaded) {
            return;
        }
        if (this.app.data['collector-definition']) {
            this._loadDefinition(this.app.data['collector-definition'] as any);
            return;
        }
        const data = await this.app.request({
            method: "GET",
            url: this.app.forum.attribute("apiUrl") + "/collector-data"
        });
        this._loadDefinition(data as any);
    }
    protected _loadDefinition(data: {
        conditions: { global: boolean, trans: string, key: string, manual: boolean, abs: boolean, update: boolean, evaluation?: string }[],
        rewards: { trans: string, key: string }[]
    }) {
        data.conditions.forEach((value) => {
            this.conditionTranslations[value.key] = value.trans;
            this.conditionsKeys.push(value.key);
            this.rawConditionDefinition[value.key] = value;
        });
        data.rewards.forEach((value) => {
            this.rewardTranslations[value.key] = value.trans;
            this.rewardsKeys.push(value.key);
        });
        this.definitionLoaded = true;
    }
    public static getInstance(app: ForumApplication | AdminApplication): HumanizeUtils {
        if (!this.instance) {
            this.instance = new HumanizeUtils(app);
        }
        return this.instance;
    }
    public getAllConditions(containsGlobal: boolean = false): ItemList<string> {
        const ret = new ItemList<string>();
        this.conditionsKeys.forEach(key => {
            if (containsGlobal || !this.rawConditionDefinition[key].global)
                ret.add(key, this.conditionTranslations[key]);
        });
        return ret;
    }
    public getAllRewards(): ItemList<string> {
        const ret = new ItemList<string>();
        this.rewardsKeys.forEach(key => {
            ret.add(key, this.rewardTranslations[key]);
        });
        return ret;
    }
    public getConditionName(key: string): string {
        if (!this.getAllConditions().has(key)) {
            return key;
        }
        return this.getAllConditions().get(key);
    }
    public getRewardName(key: string): string {
        if (!this.getAllRewards().has(key)) {
            return key;
        }
        return this.getAllRewards().get(key);
    }
    public getRewardValue(key: string, value: string): string {
        return value;
    }
    public async rewardSelection(type: string) {
        return "";
    }
    public humanizeCondition(conditionData: ConditionData[] | ConditionData): any {
        if (Array.isArray(conditionData)) {
            return conditionData.map(condition => {
                return this.humanizeCondition(condition);
            });
        } else {
            if (conditionData.alter_name) {
                return conditionData.alter_name;
            }
            let span = conditionData.span ? this.app.translator.trans("xypp-collector.forum.condition.span", { span: conditionData.span }) : '';
            if (Array.isArray(span)) {
                span = span.join("");
            }
            return this.app.translator.trans("xypp-collector.forum.condition.format", {
                b: <b />,
                name: this.getConditionName(conditionData.name),
                operator: this.app.translator.trans(`xypp-collector.lib.operator.${CONDITION_OPERATOR_MAP[conditionData.operator]}`),
                value: conditionData.value,
                calculate: this.getCalculate(conditionData.calculate || CALCULATE.SUM),
                span
            });
        }
    }

    public humanizeReward(rewardData: RewardData[] | RewardData): any {
        if (Array.isArray(rewardData)) {
            return rewardData.map(condition => {
                return this.humanizeReward(condition);
            });
        } else {
            if (rewardData.alter_name) {
                return rewardData.alter_name;
            }
            return this.app.translator.trans("xypp-collector.forum.reward.format", {
                b: <b />,
                name: this.getRewardName(rewardData.name),
                value: this.getRewardValue(rewardData.name, rewardData.value)
            });
        }
    }

    public getCalculate(calculate: CALCULATE) {
        const CALCULATE_MAPPING = {
            [CALCULATE.SUM]: 'sum',
            [CALCULATE.MAX]: 'max',
            [CALCULATE.DAY_COUNT]: 'days'
        }
        return this.app.translator.trans("xypp-collector.lib.calculate." + CALCULATE_MAPPING[calculate]);
    }
    public getRawConditionDefinition(key: string): { global: boolean, trans: string, key: string, manual: boolean, abs: boolean, update: boolean, evaluation?: string } | false {
        return this.rawConditionDefinition[key] || false;
    }
}