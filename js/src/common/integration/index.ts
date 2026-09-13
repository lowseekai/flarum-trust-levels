import { addCondition, addReward, addRewardSelection, rewardValueConvert } from "../utils/AddFrontend";
import AdminApplication from "flarum/admin/AdminApplication";
import ForumApplication from "flarum/forum/ForumApplication";
import commonSelectionModal from "../components/commonSelectionModal";

export function init(app: ForumApplication | AdminApplication, fe: string) {
    const base = `xypp-collector.${fe}.integration`
    addGroup(app, base);

    if (flarum.extensions['xypp-store'])
        addStoreItem(app, base);

    if (flarum.extensions['v17development-user-badges'])
        addBadge(app, base);
}
function addStoreItem(app: ForumApplication | AdminApplication, base: string) {
    const storeItemLoadingMap: Record<string, boolean> = {}
    // addReward("store_item", app.translator.trans(`${base}.reward.store_item`) + "");
    rewardValueConvert("store_item", function (value: string) {
        const item = app.store.getById("store-item", value);
        if (!item) {
            if (storeItemLoadingMap[value] === undefined) {
                storeItemLoadingMap[value] = true;
                app.store.find("store-item", value).then(() => {
                    m.redraw();
                }).catch(() => {
                    storeItemLoadingMap[value] = false;
                });
                return app.translator.trans(`${base}.reward.store_item_loading`) + "";
            } else if (storeItemLoadingMap[value] === false) {
                return app.translator.trans(`${base}.reward.store_item_error`) + "";
            } else {
                return app.translator.trans(`${base}.reward.store_item_loading`) + "";
            }
        }
        return item.attribute("name");
    });
    addRewardSelection("store_item", async () => {
        const items = await app.store.find("store-item") as unknown as any[];
        const itemsMap = items.reduce((map, item) => {
            map[item.id()] = item.attribute("name");
            return map;
        }, {} as Record<string, string>);
        return await commonSelectionModal.open(app,
            itemsMap,
            app.translator.trans(`${base}.reward.store_item_select`) as string,
            app.translator.trans(`${base}.reward.store_item_select_button`) as string);
    });
}
function addBadge(app: ForumApplication | AdminApplication, base: string) {
    const badgeLoadingMap: Record<string, boolean> = {}
    // addReward("badge", app.translator.trans(`${base}.reward.badge`) + "");
    rewardValueConvert("badge", function (value: string) {
        const item = app.store.getById("badges", value);
        if (!item) {
            if (badgeLoadingMap[value] === undefined) {
                badgeLoadingMap[value] = true;
                app.store.find("badges", value).then(() => {
                    m.redraw();
                }).catch(() => {
                    badgeLoadingMap[value] = false;
                });
                return app.translator.trans(`${base}.reward.badge_loading`) + "";
            } else if (badgeLoadingMap[value] === false) {
                return app.translator.trans(`${base}.reward.badge_error`) + "";
            } else {
                return app.translator.trans(`${base}.reward.badge_loading`) + "";
            }
        }
        return item.attribute("name");
    });
    addRewardSelection("badge", async () => {
        const items = await app.store.find("badges") as unknown as any[];
        const itemsMap = items.reduce((map, item) => {
            map[item.id()] = item.attribute("name");
            return map;
        }, {} as Record<string, string>);
        return await commonSelectionModal.open(app,
            itemsMap,
            app.translator.trans(`${base}.reward.badge_select`) as string,
            app.translator.trans(`${base}.reward.badge_select_button`) as string);
    });
}

function addGroup(app: ForumApplication | AdminApplication, base: string) {
    const storeItemLoadingMap: Record<string, boolean> = {}
    rewardValueConvert("group", function (value: string) {
        const item = app.store.getById("groups", value);
        if (!item) {
            if (storeItemLoadingMap[value] === undefined) {
                storeItemLoadingMap[value] = true;
                app.store.find("groups", value).then(() => {
                    m.redraw();
                }).catch(() => {
                    storeItemLoadingMap[value] = false;
                });
                return app.translator.trans(`${base}.reward.group_loading`) + "";
            } else if (storeItemLoadingMap[value] === false) {
                return app.translator.trans(`${base}.reward.group_error`) + "";
            } else {
                return app.translator.trans(`${base}.reward.group_loading`) + "";
            }
        }
        return item.attribute("nameSingular");
    });
    addRewardSelection("group", async () => {
        const items = await app.store.find("groups") as unknown as any[];
        const itemsMap = items.reduce((map, item) => {
            map[item.id()] = item.attribute("nameSingular");
            return map;
        }, {} as Record<string, string>);
        return await commonSelectionModal.open(app,
            itemsMap,
            app.translator.trans(`${base}.reward.group_select`) as string,
            app.translator.trans(`${base}.reward.group_select_button`) as string);
    });
}