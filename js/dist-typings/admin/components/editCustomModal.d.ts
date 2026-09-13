/// <reference types="mithril" />
/// <reference types="flarum/@types/translator-icu-rich" />
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import CustomCondition from '../../common/models/CustomCondition';
export default class editCustomModal extends Modal<{
    item?: CustomCondition;
    update?: (item: CustomCondition) => void;
} & IInternalModalAttrs> {
    name: string;
    display_name: string;
    evaluation: string;
    oninit(vnode: any): void;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    oncreate(vnode: any): void;
    content(): JSX.Element;
    onsubmit(e: any): Promise<void>;
}
