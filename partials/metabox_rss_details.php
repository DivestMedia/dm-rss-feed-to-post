<table class="form-table">
    <tbody>
        <?php foreach($this->option_fields[$meta['args']['group']] as $field => $data): ?>
            <tr class="user-rich-editing-wrap">
                <th scope="row">
                    <?=($data['label'])?>
                    <p class="description"><?=($data['description'])?></p>
                </th>
                <td>
                    <?php switch ($data['type']){
                        case 'grabber': ?>
                        <table>
                            <tbody>
                                <tr>
                                    <td>
                                        <p class="description"><strong>Loookup Method:</strong> </p>
                                        <select name="<?=($field)?>[]" id="<?=($field)?>-type">
                                            <?php foreach ([
                                                'ID','CSS','XPATH'
                                                ] as $option): ?>
                                                <option <?=($option==$data['value'][0]? 'selected' : '')?>><?=($option)?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">The type of DOM search</p>
                                    </td>
                                    <td>
                                        <p class="description"><strong>Search for:</strong></p>
                                        <input type="text" name="<?=($field)?>[]" id="<?=($field)?>-query" value="<?=esc_attr($data['value'][1])?>" class="regular-text">
                                        <p class="description">The query that will be used for searching</p>
                                    </td>
                                    <td>
                                        <p class="description"><strong>Grab the:</strong></p>
                                        <select name="<?=($field)?>[]" id="<?=($field)?>-grab">
                                            <?php foreach ([
                                                'Text',
                                                'HTML',
                                                'Input : Value',
                                                'Attr : src',
                                                'Attr : content',
                                                'Attr : href',
                                                'Attr : name',
                                                ] as $option):

                                                $optionslug = strtolower(preg_replace('/[^\da-z]/i', '', $option)); ?>
                                                <option value="<?=$optionslug?>" <?=($optionslug==$data['value'][2]? 'selected' : '')?>><?=($option)?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">The property of the element to grab</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <?php break;
                        case 'text': ?>
                        <?php default: ?>
                        <input type="text" name="<?=($field)?>" id="<?=($field)?>" value="<?=($data['value'])?>" class="regular-text">
                        <?php break; ?>
                        <?php } ?>
                        <?php
                        // Extra Details
                        switch ($field){
                            case '_rss_link':
                            $status = '';
                            if(!empty($data['value'])){
                                $rsslink = file_get_contents($data['value']);
                                try {
                                    $rss = new SimpleXmlElement($rsslink);
                                    if(isset($rss->channel->item)){
                                        $status = "RSS Valid." . $rss->channel->item->count() ." items found.";
                                    }else{
                                        $status = "RSS Valid but there are no items found.";
                                    }
                                }
                                catch(Exception $e){ $status = 'Invalid RSS'; }
                            }
                            ?>
                            <p class="description"><?=$status?></p>
                            <?php break; ?>
                            <?php } ?>
                        </td>
                    </tr>
                <?php endforeach;?>
            </tbody>

        </table>
        <?php if($this->preview): ?>
            <h2>Feed Test</h2>
            <p>Opening Feed:</p>
            <?php

            $url = $this->option_fields['rss_details']['_rss_link']['value'];
            $rss = simplexml_load_file($url);
            $items = $rss->channel->item;
            $data = [];
            if($items){
                $data['title'] = $items->title;
                $data['url'] = $items->link;
                $driver = new \Behat\Mink\Driver\GoutteDriver();
                $this->browser = new \Behat\Mink\Session($driver);
                $this->browser->start();
                $this->browser->visit((string)$items->link);
                $feed = $this->browser->getPage();
            }else{
                $data['error'] = 'No links found';
            }

            function getElemValue($elem,$type){
                switch ($type) {
                    case 'text':
                    return $elem->getText();
                    break;
                    case 'html':
                    return $elem->getHtml();
                    break;
                    case 'inputvalue':
                    return $elem->getValue();
                    break;
                    case 'attrsrc':
                    case 'attrcontent':
                    case 'attrhref':
                    case 'attrname':
                    $type = str_replace('attr','',$type);
                    return $elem->getAttribute($type);
                    break;
                    default:
                    return $elem->getOuterHtml();
                    break;
                }
            }

            foreach ($this->option_fields['rss_details'] as $field => $d) {
                if($d['type']!='grabber') continue;

                // Lookup Type
                switch ($d['value'][0]) {
                    case 'XPATH':
                    $elem = $feed->find('xpath', $d['value'][1]);
                    if($elem !== NULL){
                        $data[$d['label']] = getElemValue($elem,$d['value'][2]);
                    }
                    else {
                        $data[$d['label']] = 'Not found';
                    }
                    break;

                    default:
                    # code...
                    break;
                }
            }
            ?>
            <table>
                <tbody>
                    <?php foreach($data as $k=>$v): ?>
                        <tr>
                            <th><?=strtoupper($k)?></th>
                            <td><?=($v)?></td>
                        </tr>
                    <?php endforeach;?>
                </tbody>
            </table>
        <?php endif;?>
