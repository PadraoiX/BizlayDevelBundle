                        <div class="form-group">
                            <label for="valor" class="col-sm-2 control-label required">%NODENAME%</label>
                            <div class="col-sm-5">
                                <input type="text"
                                    name="%NODENAME%"
                                    id="%NODEID%"
                                    value="{% if %NODETWIGPATH% is defined %}{{ %NODETWIGPATH% }}{% endif %}"
                                    class="form-control">
                            </div>
                        </div>
