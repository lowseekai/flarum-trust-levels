import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import app from 'flarum/admin/app';
import TrustLevel from '../../common/models/TrustLevel';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import { HumanizeUtils } from '@xypp-collector/forum';
import Group from 'flarum/common/models/Group';
import { showIf } from '../../common/utils/NodeUtil';
import editModal from './editModal';

export default class adminPage extends ExtensionPage {
    items: TrustLevel[] = [];
    item_loading: boolean = false;
    sortChanged: boolean = false;
    isRemoving: Record<string, boolean> = {};
    savingSorting: boolean = false;

    oncreate(vnode: any): void {
        super.oncreate(vnode);
        HumanizeUtils.getInstance(app);
        this.loadData();
    }

    content(vnode: any) {
        return <div className="container">
            {this.buildSettingComponent({
                type: 'boolean',
                setting: 'xypp-trust-levels.no-auto-update',
                label: app.translator.trans('xypp-trust-levels.admin.no-auto-update'),
            })}
            {this.submitButton()}
            <h2>{app.translator.trans('xypp-trust-levels.admin.data')}</h2>
            <table className="xypp-trust-levels-adminPage-table Table">
                <colgroup>
                    <col className="xypp-trust-levels-adminPage-table-col-id" />
                    <col className="xypp-trust-levels-adminPage-table-col-icon" />
                    <col className="xypp-trust-levels-adminPage-table-col-name" />
                    <col className="xypp-trust-levels-adminPage-table-col-level" />
                    <col className="xypp-trust-levels-adminPage-table-col-group" />
                    <col className="xypp-trust-levels-adminPage-table-col-operation" />
                </colgroup>
                <thead>
                    <tr>
                        <th className="xypp-trust-levels-adminPage-table-id">
                            {app.translator.trans('xypp-trust-levels.admin.table.id')}
                        </th>
                        <th className="xypp-trust-levels-adminPage-table-icon">
                            <i className="fas fa-icons" aria-hidden="true"></i>
                        </th>
                        <th className="xypp-trust-levels-adminPage-table-name">
                            {app.translator.trans('xypp-trust-levels.admin.table.name')}
                        </th>
                        <th className="xypp-trust-levels-adminPage-table-level">
                            {app.translator.trans('xypp-trust-levels.admin.table.level')}
                        </th>
                        <th className="xypp-trust-levels-adminPage-table-group">
                            {app.translator.trans('xypp-trust-levels.admin.table.group')}
                        </th>
                        <th className="xypp-trust-levels-adminPage-table-operation">
                            {app.translator.trans('xypp-trust-levels.admin.table.operation')}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {this.items.map((item, index) => {
                        const itemId = item.id() || '';
                        const removing = this.isRemoving[itemId] || false;
                        const group = item.group_id() === null
                            ? undefined
                            : app.store.getById<Group>('groups', String(item.group_id()));
                        const icon = item.icon()?.trim() || 'fas fa-layer-group';

                        return (
                            <tr key={itemId}>
                                <td className="xypp-trust-levels-adminPage-table-id">{item.id()}</td>
                                <td className="xypp-trust-levels-adminPage-table-icon">
                                    <i className={icon} aria-hidden="true"></i>
                                </td>
                                <td className="xypp-trust-levels-adminPage-table-name">{item.name()}</td>
                                <td className="xypp-trust-levels-adminPage-table-level">
                                    {showIf(item.attribute<boolean>('levelChanged'), '*')}
                                    {item.level()}
                                </td>
                                <td className="xypp-trust-levels-adminPage-table-group">
                                    {group?.nameSingular() || app.translator.trans('xypp-trust-levels.admin.create-modal.null_group')}
                                </td>
                                <td className="xypp-trust-levels-adminPage-table-operation">
                                    <Button
                                        className="Button Button--primary Button--icon"
                                        icon="fas fa-edit"
                                        title="Edit trust level"
                                        aria-label="Edit trust level"
                                        onclick={this.click.bind(this)}
                                        data-id={itemId}
                                    />
                                    <Button
                                        className="Button Button--danger Button--icon"
                                        icon="fas fa-trash"
                                        title="Delete trust level"
                                        aria-label="Delete trust level"
                                        onclick={this.remove.bind(this)}
                                        data-id={itemId}
                                        disabled={removing || item.level() === 0}
                                        loading={removing}
                                    />
                                    {showIf(item.level() > 1 && !!this.items[index - 1],
                                        <Button
                                            className="Button Button--secondary Button--icon"
                                            icon="fas fa-sort-up"
                                            title="Move up"
                                            aria-label="Move trust level up"
                                            onclick={this.swap(index, -1)}
                                            data-id={itemId}
                                        />
                                    )}
                                    {showIf(!!this.items[index + 1],
                                        <Button
                                            className="Button Button--secondary Button--icon"
                                            icon="fas fa-sort-down"
                                            title="Move down"
                                            aria-label="Move trust level down"
                                            onclick={this.swap(index, 1)}
                                            data-id={itemId}
                                            disabled={item.level() === 0}
                                        />
                                    )}
                                </td>
                            </tr>
                        );
                    })}
                    <tr className="xypp-trust-levels-adminPage-table-footer">
                        <td colSpan={6}>
                            <div className="xypp-trust-levels-adminPage-table-footer-controls">
                                <Button
                                    className="Button Button--primary"
                                    icon="fas fa-plus"
                                    onclick={this.create.bind(this)}
                                >
                                    {app.translator.trans('xypp-trust-levels.admin.table.create')}
                                </Button>
                                {showIf(this.sortChanged,
                                    <Button
                                        className="Button Button--primary"
                                        icon="fas fa-save"
                                        onclick={this.submitSort.bind(this)}
                                        loading={this.savingSorting}
                                        disabled={this.savingSorting}
                                    >
                                        {app.translator.trans('xypp-trust-levels.admin.table.save-level')}
                                    </Button>
                                )}
                                {showIf(this.item_loading, <LoadingIndicator />)}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>;
    }

    create() {
        app.modal.show(editModal, {
            item: null,
            update: (item: TrustLevel) => {
                this.items.push(item);
                this.items.sort((a, b) => a.level() - b.level());
                m.redraw();
            },
        });
    }

    async loadData() {
        this.item_loading = true;
        m.redraw();

        try {
            await HumanizeUtils.getInstance(app).loadDefinition();
            const newItems = await app.store.find<TrustLevel[]>('trust-levels');
            await app.store.find('groups');
            this.items = newItems.sort((a, b) => a.level() - b.level());
        } finally {
            this.item_loading = false;
            m.redraw();
        }
    }

    click(e: MouseEvent) {
        const id = (e.currentTarget as HTMLButtonElement).getAttribute('data-id');

        if (!id) {
            return;
        }

        app.modal.show(editModal, {
            item: app.store.getById<TrustLevel>('trust-levels', id),
            update: (updatedItem: TrustLevel) => {
                this.items = this.items.map((item) => item.id() === id ? updatedItem : item);
                this.items.sort((a, b) => a.level() - b.level());
                m.redraw();
            },
        });
    }

    async remove(e: MouseEvent) {
        const id = (e.currentTarget as HTMLButtonElement).getAttribute('data-id');

        if (!id) {
            return;
        }

        const model = app.store.getById<TrustLevel>('trust-levels', id);

        if (!model || !confirm(String(app.translator.trans('xypp-trust-levels.admin.table.remove_confirm')))) {
            return;
        }

        this.isRemoving[id] = true;
        m.redraw();

        try {
            await model.delete();
            // Deleting a level compacts all higher numeric levels on the
            // server, so reload instead of only removing the deleted row.
            await this.loadData();
        } finally {
            this.isRemoving[id] = false;
            m.redraw();
        }
    }

    swap(index: number, direction: number) {
        return (() => {
            const otherIndex = index + direction;

            if (otherIndex < 0 || otherIndex >= this.items.length) {
                return;
            }

            const current = this.items[index];
            const other = this.items[otherIndex];
            const currentLevel = current.level();
            const otherLevel = other.level();

            current.pushAttributes({ level: otherLevel, levelChanged: true });
            other.pushAttributes({ level: currentLevel, levelChanged: true });
            this.sortChanged = true;
            this.items.sort((a, b) => a.level() - b.level());
            m.redraw();
        }).bind(this);
    }

    async submitSort() {
        this.savingSorting = true;
        m.redraw();

        const swapRecord: Record<string, number> = {};

        this.items.forEach((item) => {
            if (item.attribute('levelChanged')) {
                swapRecord[item.id() || ''] = item.level();
            }
        });

        try {
            await app.request({
                method: 'POST',
                url: app.forum.attribute('apiUrl') + '/trust-levels/sort',
                body: { sorts: swapRecord },
            });
            this.sortChanged = false;
            this.items.forEach((item) => item.pushAttributes({ levelChanged: false }));
        } finally {
            this.savingSorting = false;
            m.redraw();
        }
    }
}
