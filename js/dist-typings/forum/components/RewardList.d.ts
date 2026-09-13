/// <reference types="mithril" />
import Component from "flarum/common/Component";
import { RewardData } from "../../common/types/data";
export default class RewardList extends Component<{
    rewards: RewardData[];
}> {
    view(vnode: any): JSX.Element;
}
