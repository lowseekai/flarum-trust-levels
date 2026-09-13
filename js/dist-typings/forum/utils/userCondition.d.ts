import Condition from "../../common/models/Condition";
import User from "flarum/common/models/User";
export declare function getConditionMap(forceRefresh?: boolean, user?: number | string | User | null): Promise<Record<string, Condition>>;
export declare function getConditions(forceRefresh?: boolean, user?: number | string | User | null): Promise<Condition[]>;
