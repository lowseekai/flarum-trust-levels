import Component from "flarum/common/Component";
import { CALCULATE, ConditionData, OPERATOR } from "../../common/types/data";
import Stream from "mithril/stream";
import app from "flarum/admin/app";
import HumanizeUtils from "../../common/utils/HumanizeUtils";
import { showIf } from "../../common/utils/NodeUtil";
import Button from "flarum/common/components/Button";
import Select from "flarum/common/components/Select";

function noNewItem(c: any): boolean {
    return c.name !== "*";
}

export default class ConditionConfigure extends Component<{ conditions: Stream<ConditionData[]> }> {
    conditions: ConditionData[] = [];
    REG_OPERATOR: Record<string, string> = {
        '=': '=',
        '>': '>',
        '>=': '>=',
        '<': '<',
        '<=': '<=',
        '!=': '!='
    }
    REG_CALCULATE: Record<string, string> = {
    }
    REG_CONDITIONS: Record<string, string> = {}

    oninit(vnode: any): void {
        super.oninit(vnode);
        const humanize = HumanizeUtils.getInstance(app);
        const conditions = humanize.getAllConditions().toObject();
        Object.keys(conditions).forEach(item => {
            this.REG_CONDITIONS[item] = conditions[item].content;
        });

        this.REG_CONDITIONS['*'] = app.translator.trans('xypp-collector.admin.list.new_item') + "";
        this.REG_CALCULATE[CALCULATE.SUM] = humanize.getCalculate(CALCULATE.SUM) + "";
        this.REG_CALCULATE[CALCULATE.MAX] = humanize.getCalculate(CALCULATE.MAX) + "";
        this.REG_CALCULATE[CALCULATE.DAY_COUNT] = humanize.getCalculate(CALCULATE.DAY_COUNT) + "";
        this.conditions = JSON.parse(JSON.stringify(this.attrs.conditions()));
        this.conditions.push({
            name: '*',
            operator: OPERATOR.EQUAL,
            value: 0
        });
    }
    onbeforeupdate(vnode: any): void {
        this.conditions = JSON.parse(JSON.stringify(this.attrs.conditions()));
        this.conditions.push({
            name: '*',
            operator: OPERATOR.EQUAL,
            value: 0
        });
        super.onbeforeupdate(vnode);
    }
    view(vnode: any) {
        return <table className='Table condition-table'>
            <thead>
                <tr>
                    <th>{app.translator.trans('xypp-collector.admin.list.condition-name')}</th>
                    <th>{app.translator.trans('xypp-collector.admin.list.condition-operator')}</th>
                    <th>{app.translator.trans('xypp-collector.admin.list.condition-value')}</th>
                    <th>{app.translator.trans('xypp-collector.admin.list.condition-span')}</th>
                    <th>{app.translator.trans('xypp-collector.admin.list.condition-calculate')}</th>
                    <th>{app.translator.trans('xypp-collector.admin.list.condition-alter_name')}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {this.conditions.map((item, index) => {
                    return (
                        <tr>
                            <td>
                                <Select className="FormControl" value={item.name} options={this.REG_CONDITIONS} onchange={((name: string) => {
                                    if (this.conditions.length == index + 1) {
                                        this.conditions.push({
                                            name: '*',
                                            operator: OPERATOR.EQUAL,
                                            value: 0
                                        });
                                    }
                                    this.conditions[index].name = name;
                                    this.attrs.conditions(this.conditions.filter(noNewItem));;
                                }).bind(this)}>
                                </Select>
                            </td>
                            <td>
                                <Select className="FormControl" value={item.operator} options={this.REG_OPERATOR} onchange={((name: string) => {
                                    this.conditions[index].operator = name as OPERATOR;
                                    this.attrs.conditions(this.conditions.filter(noNewItem));;
                                }).bind(this)}>
                                </Select>
                            </td>
                            <td>
                                <input className="FormControl" type="text" value={item.value} onchange={((e: InputEvent) => {
                                    this.conditions[index].value = parseInt((e.target as HTMLInputElement).value);
                                    this.attrs.conditions(this.conditions.filter(noNewItem));;
                                }).bind(this)} />
                            </td>
                            <td>
                                <input className="FormControl" type="number" value={item.span} onchange={((e: InputEvent) => {
                                    this.conditions[index].span = (e.target as HTMLInputElement).value ? parseInt((e.target as HTMLInputElement).value) : undefined;
                                    this.attrs.conditions(this.conditions.filter(noNewItem));;
                                }).bind(this)} />
                            </td>
                            <td>
                                <Select className="FormControl" value={item.calculate || CALCULATE.SUM} options={this.REG_CALCULATE} onchange={((name: string) => {
                                    this.conditions[index].calculate = parseInt(name) as CALCULATE;
                                    this.attrs.conditions(this.conditions.filter(noNewItem));;
                                }).bind(this)}>
                                </Select>
                            </td>
                            <td>
                                <input className="FormControl" type="text" value={item.alter_name || ""} onchange={((e: InputEvent) => {
                                    this.conditions[index].alter_name = (e.target as HTMLInputElement).value || undefined;
                                    this.attrs.conditions(this.conditions.filter(noNewItem));;
                                }).bind(this)} />
                            </td>
                            <td>
                                {showIf(item.name != '*',
                                    <Button className="Button Button--danger" onclick={((e: any) => {
                                        this.conditions.splice(index, 1);
                                        m.redraw();
                                        this.attrs.conditions(this.conditions.filter(noNewItem));;
                                    }).bind(this)} data-id={index}>
                                        <i class="fas fa-trash"></i>
                                    </Button>
                                )}
                                {showIf((this.conditions[index - 1] && item.name != '*'),
                                    <Button className="Button Button--secondary" onclick={this.swap(index, -1)}>
                                        <i class="fas fa-sort-up"></i>
                                    </Button>
                                )}
                                {showIf((this.conditions[index + 2] && item.name != '*'),
                                    <Button className="Button Button--secondary" onclick={this.swap(index, 1)}>
                                        <i class="fas fa-sort-down"></i>
                                    </Button>
                                )}
                            </td>
                        </tr>
                    )
                })}
            </tbody>
        </table>
    }


    swap(id: number, dir: number) {
        return (() => {
            const swap1 = Math.max(id + dir, id);
            const swap2 = Math.min(id + dir, id);
            const tmp = this.conditions[swap1];
            this.conditions[swap1] = this.conditions[swap2];
            this.conditions[swap2] = tmp;
            this.attrs.conditions(this.conditions.filter(noNewItem));;
            m.redraw();
        }).bind(this);
    }
}