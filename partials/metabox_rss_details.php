<table class="form-table">
    <tbody>
        <?php foreach($this->option_fields[$meta['args']['group']] as $field => $data): ?>
            <tr class="user-rich-editing-wrap">
                <th scope="row">
                    <?php if($data['label']!=='Custom Meta'):?>
                        <?=($data['label'])?>
                        <p class="description"><?=($data['description'])?></p>
                    <?php else:?>
                        <span class="row-title"><?=($data['label'])?></span>
                        <p class="description"><strong>Meta Key:</strong> </p>
                        <?php
                        if(!empty($data['value']['meta'])): ?>
                            <?php foreach ($data['value']['meta'] as $metaid => $value): ?>
                                <?php // if(!empty($value)): ?>
                                    <input type="text" name="<?=($field)?>[meta][]" id="<?=($field)?>-meta" value="<?=esc_attr($value)?>" class="regular-text" <?=($data['required'] ? 'required' : '')?>><br>
                                <?php // endif;?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <input type="text" name="<?=($field)?>[meta]<?=($data['label']=='Custom Meta' ? '[]' : '')?>" id="<?=($field)?>-meta" value="" class="regular-text" <?=($data['required'] ? 'required' : '')?>><br>
                        <?php endif;?>
                        <p class="description">The query that will be used for searching</p>
                    <?php endif;?>
                </th>
                <td>
                    <?php switch ($data['type']){
                        case 'grabber': ?>
                        <table>
                            <tbody>
                                <tr>
                                    <td style="vertical-align:top;">
                                        <p class="description"><strong>Loookup Method:</strong> </p>
                                        <?php if(!empty($data['value'])):
                                            $inputs = ($data['label']=='Custom Meta') ? $data['value']['type'] : [$data['value']['type']]; ?>
                                            <?php foreach ($inputs as $metaid => $value): ?>
                                                <?php if(!empty($value)): ?>
                                                    <select name="<?=($field)?>[type]<?=($data['label']=='Custom Meta' ? '[]' : '')?>" id="<?=($field)?>-type" <?=($data['required'] ? 'required' : '')?>>
                                                        <?php foreach ([
                                                            'ID','NAME','CSS','XPATH'
                                                            ] as $option): ?>
                                                            <option <?=($option==$value? 'selected' : '')?>><?=($option)?></option>
                                                        <?php endforeach; ?>
                                                    </select><br>
                                                <?php endif;?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <select name="<?=($field)?>[type]<?=($data['label']=='Custom Meta' ? '[]' : '')?>" id="<?=($field)?>-type" <?=($data['required'] ? 'required' : '')?>>
                                                <option>ID</option>
                                                <option>NAME</option>
                                                <option>CSS</option>
                                                <option>XPATH</option>
                                            </select>
                                        <?php endif;?>
                                        <p class="description">The type of DOM search</p>
                                    </td>
                                    <td style="vertical-align:top;width:350px;">
                                        <p class="description"><strong>Search for:</strong></p>
                                        <?php if(!empty($data['value'])): ?>
                                            <?php
                                            $inputs = ($data['label']=='Custom Meta') ? $data['value']['query'] : [$data['value']['query']];
                                            ?>
                                            <?php foreach ($inputs as $metaid => $value): ?>
                                                <?php // if(!empty($value)): ?>
                                                    <input type="text" name="<?=($field)?>[query]<?=($data['label']=='Custom Meta' ? '[]' : '')?>" id="<?=($field)?>-query" value="<?=esc_attr($value)?>" class="regular-text" <?=($data['required'] ? 'required' : '')?>><br>
                                                <?php // endif;?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <input type="text" name="<?=($field)?>[query]<?=($data['label']=='Custom Meta' ? '[]' : '')?>" id="<?=($field)?>-query" value="" class="regular-text" <?=($data['required'] ? 'required' : '')?>>
                                        <?php endif;?>
                                        <p class="description">The query that will be used for searching</p>
                                    </td>
                                    <td style="vertical-align:top;">
                                        <p class="description"><strong>Grab the:</strong></p>

                                        <?php if(!empty($data['value'])): ?>
                                            <?php
                                            $inputs = ($data['label']=='Custom Meta') ? $data['value']['selector'] : [$data['value']['selector']];
                                            ?>
                                            <?php foreach ($inputs as $metaid => $value): ?>
                                                <?php if(!empty($value)): ?>
                                                    <select name="<?=($field)?>[selector]<?=($data['label']=='Custom Meta' ? '[]' : '')?>" id="<?=($field)?>-grab" <?=($data['required'] ? 'required' : '')?>>
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
                                                            <option value="<?=$optionslug?>" <?=($optionslug==$value? 'selected' : '')?>><?=($option)?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <br>
                                                <?php endif;?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <select name="<?=($field)?>[selector]<?=($data['label']=='Custom Meta' ? '[]' : '')?>" id="<?=($field)?>-grab" <?=($data['required'] ? 'required' : '')?>>
                                                <option value="text">Text</option>
                                                <option value="html">HTML</option>
                                                <option value="inputvalue">Input : Value</option>
                                                <option value="attrsrc">Attr : src</option>
                                                <option value="attrcontent">Attr : content</option>
                                                <option value="attrhref">Attr : href</option>
                                                <option value="attrname">Attr : name</option>
                                            </select>
                                        <?php endif;?><p class="description">The property of the element to grab</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <?php break;
                        case 'text':?>
                        <?php default: ?>
                        <input type="text" name="<?=($field)?>" id="<?=($field)?>" value="<?=($data['value'])?>" class="regular-text" <?=($data['required'] ? 'required' : '')?>>
                        <?php break; ?>
                        <?php } ?>
                        <?php
                        // Extra Details
                        switch ($field){
                            case '_rss_link':
                            $status = '';
                            if(!empty($data['value'])){
                                try{
                                        $rsslink = file_get_contents($data['value'],false,stream_context_create([
                                            'http' => [
                                                'method'  => 'GET',
                                                'user_agent '  => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36",
                                                'header' => [
                                                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8
                                                    '
                                                ]
                                            ]
                                        ]));




                                }catch(Exception $e){
                                    $status = 'Invalid Link';
                                }
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
                <tr>
                    <td colspan="2" class="text-center">
                        <button class="button" id="add-meta-button">Add Custom Meta Fields</button>
                    </td>
                </tr>
            </tbody>

        </table>
        <input name="checker" type="button" class="button button-primary button-large" id="feed-check" value="Check Feed"><br><br>
        <table id="feed-details-area">
            <tbody>

            </tbody>
        </table>
        <textarea id="log-area" rows="8" class="hidden" style="width:100%"></textarea>
