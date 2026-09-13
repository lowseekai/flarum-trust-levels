/// <reference types="mithril" />
import Component from "flarum/common/Component";
import { ConditionData } from "../../common/types/data";
import Stream from "mithril/stream";
export default class ConditionConfigure extends Component<{
    conditions: Stream<ConditionData[]>;
}> {
    conditions: ConditionData[];
    REG_OPERATOR: Record<string, string>;
    REG_CALCULATE: Record<string, string>;
    REG_CONDITIONS: Record<string, string>;
    oninit(vnode: any): void;
    onbeforeupdate(vnode: any): void;
    view(vnode: any): JSX.Element;
    swap(id: number, dir: number): () => void;
}
