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
    let data: { id: number | string } | undefined;
    if (user) {
        if (user instanceof User) {
            const userId = user.id();
            if (userId) {
                data = { id: userId };
            }
        }
        else data = { id: user };
    }
    let conditions = app.store.all<Condition>("condition");
    const query = data;
    if (query) {
        conditions = conditions.filter(c => c.global() || c.user_id() == query.id);
    }

    if (forceRefresh || conditions.length == 0) {
        conditions = await app.store.find<Condition[]>('collector-condition', query as any);
    }
    return conditions;
}
