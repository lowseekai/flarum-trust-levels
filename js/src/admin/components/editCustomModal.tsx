import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import Select from 'flarum/common/components/Select';
import Switch from "flarum/common/components/Switch"
import { showIf } from '../../common/utils/NodeUtil';
import Stream from 'flarum/common/utils/Stream';
import CustomCondition from '../../common/models/CustomCondition';
import HumanizeUtils from '../../common/utils/HumanizeUtils';

export default class editCustomModal extends Modal<{
    item?: CustomCondition,
    update?: (item: CustomCondition) => void
} & IInternalModalAttrs> {
    name: string = "";
    display_name: string = "";
    evaluation: string = "";
    oninit(vnode: any): void {
        super.oninit(vnode);
        const humanize = HumanizeUtils.getInstance(app);
        if (this.attrs.item) {
            this.name = this.attrs.item.name();
            this.display_name = this.attrs.item.display_name();
            this.evaluation = this.attrs.item.evaluation();
        }
    }
    className() {
        return 'Modal Modal--large';
    }
    title() {
        return app.translator.trans('xypp-collector.admin.custom.title');
    }
    oncreate(vnode: any): void {
        super.oncreate(vnode);
    }
    content() {
        const that = this;
        const humanize = HumanizeUtils.getInstance(app);
        return (
            <div className="Modal-body">
                <div className="Form">
                    <div className="Form-group">
                        <label for="xypp-collector-custom-ipt-name">{app.translator.trans('xypp-collector.admin.custom.name')}</label>
                        <input id="xypp-collector-custom-ipt-name" required className="FormControl" type="text" step="any" value={this.name} onchange={((e: InputEvent) => {
                            this.name = (e.target as HTMLInputElement).value;
                        }).bind(this)} />
                    </div>
                    <div className="Form-group">
                        <label for="xypp-collector-custom-ipt-display_name">{app.translator.trans('xypp-collector.admin.custom.display_name')}</label>
                        <input id="xypp-collector-custom-ipt-display_name" required className="FormControl" type="text" step="any" value={this.display_name} onchange={((e: InputEvent) => {
                            this.display_name = (e.target as HTMLInputElement).value;
                        }).bind(this)} />
                    </div>
                    <div className="Form-group">
                        <label for="xypp-collector-custom-ipt-evaluation">{app.translator.trans('xypp-collector.admin.custom.evaluation')}</label>
                        <textarea id="xypp-collector-custom-ipt-evaluation" required className="FormControl" step="any" value={this.evaluation} onchange={((e: InputEvent) => {
                            this.evaluation = (e.target as HTMLTextAreaElement).value;
                        }).bind(this)}>{this.evaluation}</textarea>
                    </div>
                    <div className="Form-group">
                        <Button class="Button Button--primary" type="submit" loading={this.loading}>
                            {showIf(!!this.attrs.item, app.translator.trans('xypp-collector.admin.custom.button-edit'),
                                app.translator.trans('xypp-collector.admin.custom.button'))}
                        </Button>
                    </div>
                    <pre>
                        {
                            Object.keys(humanize.getAllConditions(true).toObject()).map(function (condition) {
                                return humanize.getConditionName(condition) + ": {" + condition + "}";
                            }).join("\n")
                        }
                    </pre>
                </div>
            </div>
        );
    }
    async onsubmit(e: any) {
        e.preventDefault();
        this.loading = true;
        m.redraw();
        let item = this.attrs.item || app.store.createRecord<CustomCondition>(CustomCondition.type);

        try {
            const newItem = await item.save({
                name: this.name,
                display_name: this.display_name,
                evaluation: this.evaluation
            });

            this.attrs.update && this.attrs.update(newItem);
            app.modal.close();
        } finally {
            this.loading = false;
            m.redraw();
        }
    }
}
