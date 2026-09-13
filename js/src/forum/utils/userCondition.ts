import app from "flarum/forum/app";
import Condition from "../../common/models/Condition";
import User from "flarum/common/models/User";
export async function getConditionMap(forceRefresh: boolean = false, user: number | string | User | null = null): Promise<Record<string, Condition>> {
    const conditions = await getConditions(forceRefresh, user);
    const conditionMap: Record<string, Condition> = {};
    conditions.forEach((item) => {
        conditionMap[item.name()] = item;
    });
    return conditionMap;
}
export async function getConditions(forceRefresh: boolean = false, user: number | string | User | null = null): Promise<Condition[]> {
    let data = undefined;
    if (user) {
        if (user instanceof User) data = { id: user.id() };
        else data = { id: user };
    }
    let conditions = app.store.all<Condition>("collector-condition");
    if (data && data.id) {
        conditions = conditions.filter(c => c.global() || c.user_id() == data.id);
    }

    if (forceRefresh || conditions.length == 0) {
        conditions = await app.store.find<Condition[]>('collector-condition', data as any);
    }
    return conditions;
}