import ExtensionPage from "flarum/admin/components/ExtensionPage";
import app from 'flarum/admin/app';
import Button from "flarum/common/components/Button";
import { showIf } from "../../common/utils/NodeUtil";
import LoadingIndicator from "flarum/common/components/LoadingIndicator";
import HumanizeUtils from "../../common/utils/HumanizeUtils";
import Checkbox from "flarum/common/components/Checkbox";
import LinkButton from "flarum/common/components/LinkButton";
import Tooltip from "flarum/common/components/Tooltip";
import Stream from "mithril/stream";
import type Tag from "@flarum-tags/common/models/Tag";
import CustomCondition from "../../common/models/CustomCondition";
import editCustomModal from "./editCustomModal";

export default class adminPage extends ExtensionPage {
    loadingData: boolean = false;
    autoEmit?: Stream<string>
    autoEmitObj: Record<string, Record<string, boolean>> = {};
    invalidTags?: Stream<string>;
    invalidTagsObj: Record<string, boolean | string[]> = {};
    customs?: CustomCondition[];
    deletingCustom: Record<string, boolean> = {};
    oncreate(vnode: any): void {
        this.autoEmit = this.setting('xypp.collector.emit_control', "{}");
        this.autoEmitObj = JSON.parse(this.autoEmit!());
        if (Array.isArray(this.autoEmitObj)) this.autoEmitObj = {};
        this.invalidTags = this.setting('xypp.collector.invalid_tags', "{}");
        this.invalidTagsObj = JSON.parse(this.invalidTags!());
        if (Array.isArray(this.invalidTagsObj)) this.invalidTagsObj = {};
        super.oncreate(vnode);
        this.loadData();
    }
    content(vnode: any) {
        return <div className="xypp-collector-adminPage-container container">
            <div className="Form-group">
                <h2>{app.translator.trans('xypp-collector.admin.emit_control.title')}</h2>
                <table className="Table">
                    <thead>
                        <tr>
                            <th>{app.translator.trans('xypp-collector.admin.emit_control.name')}</th>
                            <th>
                                <LinkButton onclick={this.toggleAll("event")}>
                                    {app.translator.trans('xypp-collector.admin.emit_control.event')}
                                </LinkButton>
                            </th>
                            <th>
                                <LinkButton onclick={this.toggleAll("update")}>
                                    {app.translator.trans('xypp-collector.admin.emit_control.update')}
                                </LinkButton>
                            </th>
                            <th>
                                <LinkButton onclick={this.toggleAll("manual")}>
                                    {app.translator.trans('xypp-collector.admin.emit_control.manual')}
                                </LinkButton>
                            </th>
                            <th>
                                <LinkButton onclick={this.toggleAll("abs")}>
                                    {app.translator.trans('xypp-collector.admin.emit_control.abs')}
                                </LinkButton>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {this.getControls()}
                    </tbody>
                </table>
            </div>
            {showIf(this.loadingData, <LoadingIndicator />)}
            {this.submitButton()}
            <div className="Form-group valid-tags">
                <h3>{app.translator.trans("xypp-collector.admin.valid_tag_discussion")}</h3>
                <div className="xypp-collector-valid-tags-check">
                    {this.getValidTagsDiscussion()}
                </div>
            </div>
            <div className="Form-group valid-tags">
                <h3>{app.translator.trans("xypp-collector.admin.valid_tag_post")}</h3>
                <div className="xypp-collector-valid-tags-check">
                    {this.getValidTagsPost()}
                </div>
            </div>
            {
                this.buildSettingComponent({
                    setting: "xypp.collector.max_keep",
                    label: app.translator.trans('xypp-collector.admin.max_keep'),
                    type: "number",
                    min: 1
                })
            }
            {
                this.buildSettingComponent({
                    setting: "xypp.collector.use_custom",
                    label: app.translator.trans('xypp-collector.admin.use_custom'),
                    type: "boolean"
                })
            }
            {
                this.buildSettingComponent({
                    setting: "xypp.collector.custom-normal-update",
                    label: app.translator.trans('xypp-collector.admin.custom_normal_update'),
                    type: "boolean"
                })
            }
            {
                this.buildSettingComponent({
                    setting: "xypp.collector.custom-global-update",
                    label: app.translator.trans('xypp-collector.admin.custom_global_update'),
                    type: "boolean"
                })
            }
            {
                this.buildSettingComponent({
                    setting: "xypp.collector.auto_update",
                    label: app.translator.trans('xypp-collector.admin.auto_update'),
                    type: "boolean"
                })
            }{
                this.buildSettingComponent({
                    setting: "xypp.collector.auto_update_hour",
                    label: app.translator.trans('xypp-collector.admin.auto_update_hour'),
                    type: "number"
                })
            }
            {this.submitButton()}

            <div>
                <h2>{app.translator.trans("xypp-collector.admin.custom.title")}</h2>
                <table>
                    <thead>
                        <tr>
                            <th>{app.translator.trans("xypp-collector.admin.custom.name")}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {
                            this.customs?.map(custom => <tr className="custom-row">
                                <td>{custom.display_name()}</td>
                                <td>
                                    <Button className="Button Button--primary" onclick={this.customEdit(custom)}>
                                        <i class="fas fa-edit"></i>
                                    </Button>
                                    <Button className="Button Button--primary" onclick={this.customDelete(custom)}>
                                        <i class="fas fa-trash-alt"></i>
                                    </Button>
                                </td>
                            </tr>)
                        }
                        <tr>
                            <td colspan="2">
                                <Button className="Button Button--primary" onclick={this.customAdd()}>
                                    <i class="fas fa-plus"></i>
                                    {app.translator.trans("xypp-collector.admin.custom.add")}
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div >
    }

    async loadData() {
        this.loadingData = true;
        m.redraw();
        await HumanizeUtils.getInstance(app).loadDefinition();
        this.customs = await app.store.find<CustomCondition[]>(CustomCondition.type);
        this.loadingData = false;
        m.redraw();
    }

    getControls() {
        if (this.loadingData) return [];
        const conditions = HumanizeUtils.getInstance(app).getAllConditions(true).toObject();
        return Object.keys(conditions).map(key => <tr className="emit-control-row">
            <td className="emit-control-label">
                <LinkButton onclick={this.toggleRow(key)}>
                    {conditions[key].content}
                </LinkButton>
            </td>
            <td className="emit-control-event">
                <Checkbox onchange={this.changeStateCbMaker("event", key)}
                    state={this.checked("event", key)}
                    disabled={!this.checkType("event", key)}
                />
            </td>
            <td className="emit-control-update">
                <Checkbox onchange={this.changeStateCbMaker("update", key)}
                    state={this.checked("update", key)}
                    disabled={!this.checkType("update", key)}
                />
            </td>
            <td className="emit-control-manual">
                <Checkbox onchange={this.changeStateCbMaker("manual", key)}
                    state={this.checked("manual", key)}
                    disabled={!this.checkType("manual", key)}
                />
            </td>
            <td className="emit-control-abs">
                <Checkbox onchange={this.changeStateCbMaker("abs", key)}
                    state={this.checked("abs", key)}
                />
                {showIf(this.checkType("abs", key), "",
                    <span>
                        <Tooltip text={app.translator.trans("xypp-collector.admin.limited_accumulation_support")}>
                            <i class="fas fa-exclamation-triangle"></i>
                        </Tooltip>
                    </span>
                )}
            </td>
        </tr>
        );
    }
    checked(type: string, name: string): boolean {
        if (!this.checkType(type, name) && type != "abs") return false;
        if (!this.autoEmitObj[type]) return true;
        return this.autoEmitObj[type][name] !== true;
    }
    checkType(type: string, name: string): boolean {
        const def = HumanizeUtils.getInstance(app).getRawConditionDefinition(name);
        if (type === "event") return !(def && def.manual);
        if (type === "update") return def && def.update;
        if (type === "manual") return def && def.manual;
        if (type === "abs") return def && def.abs;
        return false
    }
    toggleRow(name: string) {
        return ((e: MouseEvent) => {
            e.preventDefault();
            const types = ["event", "update", "manual"].filter(type => this.checkType(type, name)).concat("abs");

            const target = types.find(type => {
                return this.autoEmitObj[type] && this.autoEmitObj[type][name]
            }) === undefined;

            types.forEach(type => {
                if (!this.autoEmitObj[type]) this.autoEmitObj[type] = {}
                if (target) this.autoEmitObj[type][name] = true;
                else if (this.autoEmitObj[type][name]) delete this.autoEmitObj[type][name];
            });

            this.autoEmit!(JSON.stringify(this.autoEmitObj));
        }).bind(this);
    }
    toggleAll(type: string) {
        return ((e: MouseEvent) => {
            e.preventDefault();
            const all = Object.keys(HumanizeUtils.getInstance(app).getAllConditions(true).toObject()).filter(key => this.checkType(type, key) || type === "abs");
            if (!this.autoEmitObj[type]) this.autoEmitObj[type] = {};
            let target = false;
            if (Object.keys(this.autoEmitObj[type]).length == 0) target = true;
            all.forEach(key => {
                if (target) this.autoEmitObj[type][key] = true;
                else if (this.autoEmitObj[type][key]) delete this.autoEmitObj[type][key];
            });
            this.autoEmit!(JSON.stringify(this.autoEmitObj));
        }).bind(this)
    }
    changeStateCbMaker(type: string, name: string) {
        return ((e: boolean) => {
            if (!this.autoEmitObj[type]) this.autoEmitObj[type] = {};
            if (!e) this.autoEmitObj[type][name] = true;
            else if (this.autoEmitObj[type][name]) delete this.autoEmitObj[type][name];
            this.autoEmit!(JSON.stringify(this.autoEmitObj));
        }).bind(this);
    }

    getValidTagsDiscussion() {
        return app.store.all<Tag>("tags").map(tag => {
            return <Checkbox onchange={this.changeValidTagsStateCbMaker(tag, "discussion")} state={this.isValidTags(tag.id() || "0", "discussion")}>
                {tag.name()}
            </Checkbox>
        })
    }
    getValidTagsPost() {
        return app.store.all<Tag>("tags").map(tag => {
            return <Checkbox onchange={this.changeValidTagsStateCbMaker(tag, "post")} state={this.isValidTags(tag.id() || "0", "post")}>
                {tag.name()}
            </Checkbox>
        })
    }
    isValidTags(tagId: string, source: string) {
        if (this.invalidTagsObj[tagId || 0] === true) return false;
        else if (Array.isArray(this.invalidTagsObj[tagId || 0]))
            return !((this.invalidTagsObj[tagId || 0] as string[]).includes(source));

        return true;
    }
    changeValidTagsStateCbMaker(tag: Tag, source: string) {
        return ((e: boolean) => {
            const id = tag.id() || "0";
            // Backward compatibility
            if (!Array.isArray(this.invalidTagsObj[id])) {
                if (this.invalidTagsObj[id] === true) {
                    this.invalidTagsObj[id] = ["post", "discussion"];
                } else {
                    this.invalidTagsObj[id] = [];
                }
            }

            if (!e) {
                if (!(this.invalidTagsObj[id] as string[]).includes(source))
                    (this.invalidTagsObj[id] as string[]).push(source);
            } else {
                if ((this.invalidTagsObj[id] as string[]).includes(source))
                    this.invalidTagsObj[id] = (this.invalidTagsObj[id] as string[]).filter(s => s != source);
                if ((this.invalidTagsObj[id] as string[]).length == 0)
                    delete this.invalidTagsObj[id];
            }
            this.invalidTags!(JSON.stringify(this.invalidTagsObj));
        }).bind(this);
    }


    customEdit(custom: CustomCondition) {
        return (e: any) => {
            e.preventDefault();
            app.modal.show(editCustomModal, {
                item: custom,
                update: (item: CustomCondition) => {
                    this.customs = this.customs?.map(c => c.id() == item.id() ? item : c);
                    m.redraw();
                }
            });
        }
    }
    customAdd() {
        return (e: any) => {
            e.preventDefault();
            app.modal.show(editCustomModal, {
                update: (item: CustomCondition) => {
                    this.customs?.push(item);
                    m.redraw();
                }
            });
        }
    }
    customDelete(custom: CustomCondition) {
        return (e: any) => {
            e.preventDefault();
            if (!confirm(app.translator.trans("xypp-collector.admin.custom.delete-confirm") + "")) return;
            this.deletingCustom[custom.id() + ""] = true;
            m.redraw();
            custom.delete().then(() => {
                this.customs = this.customs?.filter(c => c.id() != custom.id())
                delete this.deletingCustom[custom.id() + ""];
                m.redraw();
            });
        }
    }

}