/// <reference types="mithril" />
import Component from "flarum/common/Component";
import { RewardData } from "../../common/types/data";
import Stream from "mithril/stream";
export default class RewardConfigure extends Component<{
    rewards: Stream<RewardData[]>;
}> {
    rewards: RewardData[];
    REG_REWARDS: Record<string, string>;
    rewardGettingValue: Record<number, boolean>;
    oninit(vnode: any): void;
    onbeforeupdate(vnode: any): void;
    view(vnode: any): JSX.Element;
    getValue(e: MouseEvent): Promise<void>;
}
