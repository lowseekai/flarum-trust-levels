import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import Select from 'flarum/common/components/Select';
import Switch from 'flarum/common/components/Switch';
import TrustLevel from '../../common/models/TrustLevel';
import { showIf } from '../../common/utils/NodeUtil';
import Stream from 'flarum/common/utils/Stream';

import type { ConditionData } from '../../collector/common/types/data';
import { ConditionConfigure } from '../../collector/admin';
import Group from 'flarum/common/models/Group';

const ICON_PRESETS = [
    'fas fa-user',
    'fas fa-user-plus',
    'fas fa-seedling',
    'fas fa-shield-halved',
    'fas fa-medal',
    'fas fa-star',
    'fas fa-crown',
    'fas fa-gem',
    'fas fa-trophy',
    'fas fa-layer-group',
    'fas fa-fire',
    'fas fa-users',
    'fas fa-handshake',
];

export default class editModal extends Modal<{
    item?: TrustLevel,
    update: (item: TrustLevel) => void,
} & IInternalModalAttrs> {
    conditions: Stream<ConditionData[]> = Stream([]);
    name: string = '';
    icon: string = '';
    level: number = 0;
    group_id: number | null = -1;
    allow_downgrade: boolean = false;
    downgrade_grace_days: number = 0;
    manual_only: boolean = false;

    groups: Record<string, string> = {
        '-1': String(app.translator.trans('xypp-trust-levels.admin.create-modal.null_group')),
    };

    loadedLevels: Record<string, string> = {};
    referenceLevelId: string = '';

    oninit(vnode: any): void {
        super.oninit(vnode);

        const nextLevel = app.store.all<TrustLevel>('trust-levels')
            .reduce((max, trustLevel) => Math.max(max, trustLevel.level()), -1) + 1;
        this.level = nextLevel;
        this.icon = ICON_PRESETS[0];
        this.allow_downgrade = false;
        this.downgrade_grace_days = 0;
        this.manual_only = false;

        if (this.attrs.item) {
            this.conditions = new Stream(this.attrs.item.condition() || []);
            this.name = this.attrs.item.name();
            this.level = this.attrs.item.level();
            this.icon = this.attrs.item.icon() || '';
            this.group_id = this.attrs.item.group_id() ?? -1;
            this.allow_downgrade = this.attrs.item.allow_downgrade() ?? false;
            this.downgrade_grace_days = this.attrs.item.downgrade_grace_days() ?? 0;
            this.manual_only = this.attrs.item.manual_only() ?? false;
        }

        app.store.all<Group>('groups').forEach((group) => {
            this.groups[group.id() + ''] = group.nameSingular();
        });

        let referenceLevel = Number.NEGATIVE_INFINITY;

        app.store.all<TrustLevel>('trust-levels').forEach((trustLevel) => {
            const id = trustLevel.id();
            const eligible = !this.attrs.item || this.level > trustLevel.level();

            if (id && eligible && trustLevel.level() > referenceLevel) {
                referenceLevel = trustLevel.level();
                this.referenceLevelId = id;
            }

            if (id) {
                this.loadedLevels[id] = `#${trustLevel.level()}:${trustLevel.name()}`;
            }
        });
    }

    className() {
        return 'Modal level-modal';
    }

    title() {
        if (this.attrs.item) {
            return app.translator.trans('xypp-trust-levels.admin.create-modal.edit', [this.attrs.item] as any);
        }

        return app.translator.trans('xypp-trust-levels.admin.create-modal.title');
    }

    content() {
        return (
            <div className="Modal-body">
                <div className="Form">
                    <div className="Form-group">
                        <label for="xypp-trust-levels-create-ipt-name">
                            {app.translator.trans('xypp-trust-levels.admin.create-modal.name')}
                        </label>
                        <input
                            id="xypp-trust-levels-create-ipt-name"
                            required
                            className="FormControl"
                            type="text"
                            value={this.name}
                            onchange={((e: InputEvent) => {
                                this.name = (e.target as HTMLInputElement).value;
                            }).bind(this)}
                        />
                    </div>
                    <div className="Form-group xypp-trust-levels-icon-group">
                        <label>{app.translator.trans('xypp-trust-levels.admin.create-modal.icon')}</label>
                        <div className="helpText">
                            {app.translator.trans('xypp-trust-levels.admin.create-modal.icon-help')}
                        </div>
                        <div className="xypp-trust-levels-icon-picker" role="group">
                            {ICON_PRESETS.map((icon) => (
                                <Button
                                    className={`Button Button--secondary Button--icon ${this.icon === icon ? 'Button--primary is-selected' : ''}`}
                                    icon={icon}
                                    title={icon}
                                    aria-label={icon}
                                    onclick={() => {
                                        this.icon = icon;
                                    }}
                                />
                            ))}
                        </div>
                    </div>
                    <div className="Form-group">
                        <label>{app.translator.trans('xypp-trust-levels.admin.create-modal.condition')}</label>
                        <div className="xypp-trust-levels-create-copy-controls">
                            <Select
                                options={this.loadedLevels}
                                value={this.referenceLevelId}
                                onchange={((id: string) => {
                                    this.referenceLevelId = id;
                                }).bind(this)}
                            />
                            <Button className="Button Button--primary" onclick={this.copyData.bind(this)}>
                                {app.translator.trans('xypp-trust-levels.admin.create-modal.copy-data')}
                            </Button>
                            <Button className="Button Button--primary" onclick={this.copyName.bind(this)}>
                                {app.translator.trans('xypp-trust-levels.admin.create-modal.copy-name')}
                            </Button>
                        </div>
                        <ConditionConfigure conditions={this.conditions} />
                    </div>

                    <div className="Form-group xypp-trust-levels-policy-group">
                        <Switch
                            state={this.allow_downgrade}
                            disabled={this.level === 0 || this.manual_only}
                            onchange={((value: boolean) => {
                                this.allow_downgrade = value;
                            }).bind(this)}
                        >
                            {app.translator.trans('xypp-trust-levels.admin.create-modal.allow-downgrade')}
                        </Switch>
                        <div className="helpText">
                            {app.translator.trans('xypp-trust-levels.admin.create-modal.allow-downgrade-help')}
                        </div>
                    </div>

                    <div className="Form-group xypp-trust-levels-policy-group">
                        <label for="xypp-trust-levels-create-ipt-grace-days">
                            {app.translator.trans('xypp-trust-levels.admin.create-modal.downgrade-grace-days')}
                        </label>
                        <input
                            id="xypp-trust-levels-create-ipt-grace-days"
                            className="FormControl"
                            type="number"
                            min="0"
                            max="3650"
                            value={this.downgrade_grace_days}
                            disabled={this.level === 0 || !this.allow_downgrade || this.manual_only}
                            onchange={((e: InputEvent) => {
                                const value = parseInt((e.target as HTMLInputElement).value, 10);
                                this.downgrade_grace_days = Number.isNaN(value) ? 0 : Math.max(0, Math.min(3650, value));
                            }).bind(this)}
                        />
                        <div className="helpText">
                            {app.translator.trans('xypp-trust-levels.admin.create-modal.downgrade-grace-days-help')}
                        </div>
                    </div>

                    <div className="Form-group xypp-trust-levels-policy-group">
                        <Switch
                            state={this.manual_only}
                            disabled={this.level === 0}
                            onchange={((value: boolean) => {
                                this.manual_only = value;
                                if (value) {
                                    this.allow_downgrade = false;
                                }
                            }).bind(this)}
                        >
                            {app.translator.trans('xypp-trust-levels.admin.create-modal.manual-only')}
                        </Switch>
                        <div className="helpText">
                            {app.translator.trans('xypp-trust-levels.admin.create-modal.manual-only-help')}
                        </div>
                    </div>

                    <div className="Form-group">
                        <label for="xypp-trust-levels-create-ipt-re_available">
                            {app.translator.trans('xypp-trust-levels.admin.create-modal.group')}
                        </label>
                        <Select
                            className="FormControl"
                            value={String(this.group_id ?? -1)}
                            options={this.groups}
                            onchange={((id: string) => {
                                const parsed = parseInt(id, 10);
                                this.group_id = Number.isNaN(parsed) || parsed <= 0 ? null : parsed;
                            }).bind(this)}
                        />
                    </div>
                </div>
                <div className="Form-group">
                    <Button className="Button Button--primary" type="submit" loading={this.loading}>
                        {showIf(
                            !!this.attrs.item,
                            app.translator.trans('xypp-trust-levels.admin.create-modal.button-edit'),
                            app.translator.trans('xypp-trust-levels.admin.create-modal.button')
                        )}
                    </Button>
                </div>
            </div>
        );
    }

    async onsubmit(e: any) {
        e.preventDefault();
        this.loading = true;
        m.redraw();

        const item = this.attrs.item || app.store.createRecord<TrustLevel>('trust-levels');

        try {
            const conditions = this.conditions();
            const newItem = await item.save({
                conditions: conditions.filter((condition: ConditionData) => condition.name !== '*'),
                name: this.name,
                icon: this.icon,
                group_id: this.group_id,
                allow_downgrade: this.level > 0 && this.allow_downgrade && !this.manual_only,
                downgrade_grace_days: this.level > 0 ? this.downgrade_grace_days : 0,
                manual_only: this.level > 0 && this.manual_only,
            });

            this.attrs.update && this.attrs.update(newItem);
            app.modal.close();
        } finally {
            this.loading = false;
            m.redraw();
        }
    }

    copyFrom(override: boolean = false) {
        const data = this.conditions();
        const targetModel = this.referenceLevelId
            ? app.store.getById<TrustLevel>('trust-levels', this.referenceLevelId)
            : undefined;

        if (!targetModel) {
            return;
        }

        (targetModel.condition() || []).forEach((item) => {
            const current = data.find((condition: ConditionData) =>
                condition.name === item.name &&
                condition.operator === item.operator &&
                condition.span === item.span
            );

            if (current) {
                if (override) {
                    current.calculate = item.calculate;
                    current.value = item.value;
                    current.operator = item.operator;
                    current.span = item.span;
                    current.alter_name = item.alter_name;
                }
            } else {
                data.push({ ...item });
            }
        });

        this.conditions(data);
        m.redraw();
    }

    copyData() {
        this.copyFrom(true);
    }

    copyName() {
        this.copyFrom(false);
    }
}
