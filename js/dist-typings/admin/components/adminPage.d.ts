/// <reference types="mithril" />
import ExtensionPage from "flarum/admin/components/ExtensionPage";
import Stream from "mithril/stream";
import type Tag from "@flarum-tags/common/models/Tag";
import CustomCondition from "../../common/models/CustomCondition";
export default class adminPage extends ExtensionPage {
    loadingData: boolean;
    autoEmit?: Stream<string>;
    autoEmitObj: Record<string, Record<string, boolean>>;
    invalidTags?: Stream<string>;
    invalidTagsObj: Record<string, boolean>;
    customs?: CustomCondition[];
    deletingCustom: Record<string, boolean>;
    oncreate(vnode: any): void;
    content(vnode: any): JSX.Element;
    loadData(): Promise<void>;
    getControls(): JSX.Element[];
    checked(type: string, name: string): boolean;
    checkType(type: string, name: string): boolean;
    toggleRow(name: string): (e: MouseEvent) => void;
    toggleAll(type: string): (e: MouseEvent) => void;
    changeStateCbMaker(type: string, name: string): (e: boolean) => void;
    getValidTags(): JSX.Element[];
    changeValidTagsStateCbMaker(tag: Tag): (e: boolean) => void;
    customEdit(custom: CustomCondition): (e: any) => void;
    customAdd(): (e: any) => void;
    customDelete(custom: CustomCondition): (e: any) => void;
}
