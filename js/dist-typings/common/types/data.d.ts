export declare enum OPERATOR {
    EQUAL = "=",
    NOT_EQUAL = "!=",
    GREATER_THAN = ">",
    LESS_THAN = "<",
    GREATER_THAN_OR_EQUAL = ">=",
    LESS_THAN_OR_EQUAL = "<="
}
export declare enum CALCULATE {
    SUM = 1,
    MAX = 2,
    DAY_COUNT = 3
}
export type ConditionEvent = {
    name: string;
    value: number;
};
export type ConditionData = {
    name: string;
    operator: OPERATOR;
    value: number;
    span?: number;
    calculate?: CALCULATE;
    alter_name?: string;
};
export type RewardData = {
    name: string;
    value: string;
    alter_name?: string;
};
export type ConditionAccumulation = {
    all: number;
    rest: number;
    max: number;
    days: number;
    [key: string]: number;
};
